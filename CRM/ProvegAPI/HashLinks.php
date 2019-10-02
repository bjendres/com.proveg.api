<?php
/*------------------------------------------------------------+
| ProVeg API extension                                        |
| Copyright (C) 2017-2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                      |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

/**
 * Generates tokens for personalised links
 */
class CRM_ProvegAPI_HashLinks {

  const PERSONALISED_LINKS = "PersonalisedLink";

  /**
   * Get a list of the currently configured link specs
   * @return array links (name, label, link_html, fallback_html)
   */
  public static function getLinks() {
    $links = Civi::settings()->get('proveg_personalised_links');
    if (empty($links)) {
      $links = [];
    }
    return $links;
  }

  /**
   * Get the token names of all active link specs
   *
   * @return array list of token names
   */
  public static function getLinkTokens() {
    $link_tokens = [];
    $links = self::getLinks();
    foreach ($links as $link) {
      $link_tokens[] = $link['name'];
    }
    return $link_tokens;
  }

  /**
   * Get a list of link specifications indexed by token name
   */
  public static function getLinksByTokenName() {
    $links_by_token = [];
    $links = self::getLinks();
    foreach ($links as $link) {
      $links_by_token[$link['name']] = $link;
    }
    return $links_by_token;
  }

  /**
   * Load the hash for all given contacts
   * @param $contact_ids array list of contact Ids
   * @return array list of hash by contact ID
   */
  protected static function getContactHashes($contact_ids) {
    $hashes = [];
    if ($contact_ids) {
      $query = civicrm_api3('Contact', 'get', [
          'return'       => 'hash,id',
          'option.limit' => 0,
          'id'           => ['IN' => $contact_ids]
      ]);
      foreach ($query['values'] as $contact) {
        $hashes[$contact['id']] = $contact['hash'];
      }
    }

    return $hashes;
  }



  /**
   * Handles civicrm_tokens hook
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokens
   */
  public static function addTokens(&$tokens) {
    $links = self::getLinks();
    foreach ($links as $link) {
      $tokens[self::PERSONALISED_LINKS][self::PERSONALISED_LINKS . ".{$link['name']}"] = $link['label'];
    }
  }

  /**
   * Check the contact list for email conflicts: an
   *  primary or bulk email address that is used by other contacts
   *
   * This link would undercut the concept of a personalised link, since
   *  multiple different contacts would receive it
   *
   * @param $contact_ids array list of contact IDs
   * @return array map (id => id) of contacts that have email conflicts
   */
  public static function getContactIDsWithSharedEmails($contact_ids) {
    $conflicted_contact_ids = [];
    if (!empty($contact_ids)) {
      $email_to_contact = [];
      $email_list = [];
      // first: get the emails from the given contacts
      $contact_id_list = implode(',', $contact_ids);
      $email_query = CRM_Core_DAO::executeQuery("
        SELECT 
            contact.id    AS contact_id,
            primary.email AS primary_email,
            bulk.email    AS bulk_email
        FROM civicrm_contact    contact
        LEFT JOIN civicrm_email primary ON primary.contact_id = contact.id AND primary.is_primary = 1
        LEFT JOIN civicrm_email bulk    ON bulk.contact_id = contact.id    AND bulk.is_bulk = 1
        GROUP BY contact.id");
      while ($email_query->fetch()) {
        if ($email_query->primary_email) {
          if ($email_query->contact_id != CRM_Utils_Array::value($email_query->primary_email, $email_to_contact, $email_query->contact_id)) {
            // recorded with to another contact? this is already a conflict!
            $conflicted_contact_ids[$email_query->contact_id] = $email_query->contact_id;
          } else {
            $email_to_contact[$email_query->primary_email] = $email_query->contact_id;
            $email_list[] = CRM_Core_DAO::escapeString($email_query->primary_email);
          }
        }
        if ($email_query->bulk_email) {
          if ($email_query->contact_id != CRM_Utils_Array::value($email_query->bulk_email, $email_to_contact, $email_query->contact_id)) {
            // recorded with to another contact? this is already a conflict!
            $conflicted_contact_ids[$email_query->contact_id] = $email_query->contact_id;
          } else {
            $email_to_contact[$email_query->bulk_email] = $email_query->contact_id;
            $email_list[] = CRM_Core_DAO::escapeString($email_query->bulk_email);
          }
        }
      }
      $email_query->free();

      // second round: find duplicates
      $email_list_string = '"' . implode('","', $email_list) . '"';
      $duplicates_query = CRM_Core_DAO::executeQuery("
        SELECT 
            contact.id  AS contact_id,
            email.email AS email
        FROM civicrm_email email
        LEFT JOIN civicrm_contact contact ON contact.id = email.contact_id
        WHERE email.email IN ({$email_list_string})
          AND (contact.is_deleted IS NULL OR contact.is_deleted = 0)");
      while ($duplicates_query->fetch()) {
        $email_contact_id = CRM_Utils_Array::value($duplicates_query->email, $email_to_contact, NULL);
        if ($email_contact_id) { // this should always be the case
          if ($email_contact_id != $duplicates_query->contact_id) {
            // the email is used with another contact -> conflict
            $conflicted_contact_ids[$email_contact_id] = $email_contact_id;
          }
        }
      }
      $duplicates_query->free();
    }
    return $conflicted_contact_ids;
  }

  /**
   * Handles civicrm_tokenValues hook
   * @param $values - array of values, keyed by contact id
   * @param $cids - array of contactIDs that the system needs values for.
   * @param $job - the job_id
   * @param $tokens - tokens used in the mailing - use this to check whether a token is being used and avoid fetching data for unneeded tokens
   * @param $context - the class name
   *
   * @see https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_tokenValues
   */
  public static function tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
    // find out which ones are being used first
    $link_tokens_configured = self::getLinkTokens();
    $link_tokens_used = CRM_Utils_Array::value(self::PERSONALISED_LINKS, $tokens, []);
    $links_to_be_populated = array_intersect($link_tokens_used, $link_tokens_configured);
    if (empty($links_to_be_populated)) {
      return;
    }

    // extract contact_ids
    if (is_string($cids)) {
      $contact_ids = explode(',', $cids);
    } elseif (isset($cids['contact_id'])) {
      $contact_ids = array($cids['contact_id']);
    } elseif (is_array($cids)) {
      $contact_ids = $cids;
    } else {
      Civi::log()->warning("PersonalisedLinks: Cannot interpret cids: " . json_encode($cids));
      return;
    }

    // load conflict/hash data
    $conflicted_cids = self::getContactIDsWithSharedEmails($cids);
    $good_cids = array_diff($cids, $conflicted_cids);
    $contact_hashes = self::getContactHashes($good_cids);
    $links_by_token  = self::getLinksByTokenName();

    foreach ($cids as $cid) {
      foreach ($link_tokens_used as $token) {
        $link = $links_by_token[$token];
        if (isset($conflicted_cids[$cid])) {
          // this is a conflict -> set the fallback text
          $values[$cid][self::PERSONALISED_LINKS. ".{$token}"] = $link['fallback_html'];
        } else {
          // all good -> set the link text
          $values[$cid][self::PERSONALISED_LINKS. ".{$token}"] = preg_replace('\{hash\}', $contact_hashes[$cid], $link['link_html']);
        }
      }
    }
  }
}