<?php
/*------------------------------------------------------------+
| ProVeg API extension                                        |
| Copyright (C) 2017-2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                      |
|         J. Schuppe (schuppe@systopia.de)                    |
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
 * Process ProvegSelfservice.sendlink
 *
 *  Send a personalised Editing link on demand
 *
 * @return array API result array
 * @access public
 */
function civicrm_api3_proveg_selfservice_sendlink($params)
{
  // preprocess incoming call
  CRM_ProvegAPI_Processor::preprocessCall($params, 'ProvegSelfservice.sendlink');

  // get templates
  $template_email_known   = (int) CRM_ProvegAPI_Configuration::getSetting('selfservice_link_request_template');
  $template_email_unknown = (int) CRM_ProvegAPI_Configuration::getSetting('selfservice_link_request_template_fallback');

  // find contact ids for the given email
  $contact_ids = [];
  $query = civicrm_api3('Email', 'get', [
      'option.limit' => 0,
      'email'        => trim($params['email']),
      'return'       => 'contact_id'
  ]);
  foreach ($query['values'] as $email) {
    $contact_ids[] = $email['contact_id'];
  }

  // remove the contacts that are deleted
  if ($contact_ids) {
    $query = civicrm_api3('Contact', 'get', [
        'option.limit' => 0,
        'id'           => ['IN' => $contact_ids],
        'is_deleted'   => 1,
        'return'       => 'id',
    ]);
    foreach ($query['values'] as $deleted_contact) {
      unset($contact_ids[$deleted_contact['id']]);
    }
  }

  if ($contact_ids && $template_email_known) {
    // we found a contact -> send to the first one
    $contact_id = reset($contact_ids);
    civicrm_api3('MessageTemplate', 'send', [
        'id'         => $template_email_known,
        'to_name'    => civicrm_api3('Contact', 'getvalue', ['id' => $contact_id, 'return' => 'display_name']),
        'contact_id' => $contact_id,
        'to_email'   => trim($params['email']),
    ]);

    return civicrm_api3_create_success("email sent");

  } elseif (!$contact_ids && $template_email_unknown) {
    // no contact found
    civicrm_api3('MessageTemplate', 'send', [
        'id'         => $template_email_unknown,
        'to_email'   => trim($params['email']),
    ]);

    return civicrm_api3_create_success("email sent");

  } else {
    // no template set for this case -> do nothing
    Civi::log()->debug("ProvegSelfservice.sendlink requested but not enabled");
    civicrm_api3_create_error("disabled");
  }
}

/**
 * API specs for ProvegSelfservice.sendlink
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_proveg_selfservice_sendlink_spec(&$params) {
  // CONTACT BASE
  $params['email'] = array(
    'name'           => 'email',
    'api.required'   => 1,
    'title'          => 'email address',
    );
}