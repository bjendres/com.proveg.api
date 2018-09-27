<?php
/*------------------------------------------------------------+
| ProVeg API extension                                        |
| Copyright (C) 2017 SYSTOPIA                                 |
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

class CRM_ProvegAPI_Submission {

  /**
   * The default ID of the "Work" location type.
   */
  const LOCATION_TYPE_ID_WORK = 2;

  /**
   * The financial type to create contributions with.
   */
  const FINANCIAL_TYPE_ID = 1;

  /**
   * The ID of the group to use for newsletter registrations.
   * TODO: Set to actual group ID.
   */
  const NEWSLETTER_GROUP_ID = 1;

  /**
   * The name of the payment method used for PayPal contributions.
   * TODO: Set to actual pament instrument ID.
   */
  const PAYMENT_INSTRUMENT_ID_PAYPAL = 'Cash';

  /**
   * The custom field ID for the membership sub type.
   */
  const MEMBERSHIP_SUB_TYPE_FIELD_ID = 4;

  /**
   * The default value to use for the contribution source.
   */
  const CONTRIBUTION_SOURCE_DEFAULT = 'ProVeg API';

  /**
   * Buffer days to still collect the money,
   *  i.e. you cannot submit a membership to start the 1st of may on april 30th,
   *  because the mandate would not be collected any more
   */
  const CONTRIBUTION_BUFFER_DAYS = 5;



  /**
   * Retrieves the contact matching the given contact data or creates a new
   * contact.
   *
   * @param string $contact_type
   *   The contact type to look for/to create.
   * @param array $contact_data
   *   Data to use for contact lookup/to create a contact with.
   *
   * @return int | NULL
   *   The ID of the matching/created contact, or NULL if no matching contact
   *   was found and no new contact could be created.
   * @throws \CiviCRM_API3_Exception | API_Exception
   *   When invalid data was given.
   */
  public static function getContact($contact_type, $contact_data) {
    // If no parameters are given, do nothing.
    if (empty($contact_data)) {
      return NULL;
    }

    // Prepare values: country.
    if (!empty($contact_data['country'])) {
      if (is_numeric($contact_data['country'])) {
        // If a country ID is given, update the parameters.
        $contact_data['country_id'] = $contact_data['country'];
        unset($contact_data['country']);
      }
      else {
        // Look up the country depending on the given ISO code.
        $country = civicrm_api3('Country', 'get', array(
            'check_permissions' => 0,
            'iso_code' => $contact_data['country']));
        if (!empty($country['id'])) {
          $contact_data['country_id'] = $country['id'];
          unset($contact_data['country']);
        }
        else {
          throw new API_Exception("Unknown country '{$contact_data['country']}'", 1);
        }
      }
    }

    // Pass to XCM.
    $contact_data['contact_type'] = $contact_type;
    $contact_data['check_permissions'] = 0;
    $contact = civicrm_api3('Contact', 'getorcreate', $contact_data);
    if (empty($contact['id'])) {
      return NULL;
    }

    return $contact['id'];
  }

  /**
   * Get the next possible start date,
   * which is the next 1st of the month
   * respecting the buffer days
   */
  public static function getStartDate() {
    $buffer = self::CONTRIBUTION_BUFFER_DAYS;
    $earliest = strtotime("now + {$buffer} days");
    while (date('j', $earliest) > 1) {
      // get to the next day
      $earliest = strtotime("+1 day", $earliest);
    }
    return date('Y-m-d', $earliest);
  }

  /**
   * Share an organisation's work address, unless the contact already has one
   *
   * @param $contact_id
   *   The ID of the contact to share the organisation address with.
   * @param $organisation_id
   *   The ID of the organisation whose address to share with the contact.
   * @param $location_type_id
   *   The ID of the location type to use for address lookup.
   *
   * @return boolean
   *   Whether the organisation address has been shared with the contact.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function shareWorkAddress($contact_id, $organisation_id, $location_type_id = self::LOCATION_TYPE_ID_WORK) {
    if (empty($organisation_id)) {
      // Only if organisation exists.
      return FALSE;
    }

    // Check whether organisation has a WORK address.
    $existing_org_addresses = civicrm_api3('Address', 'get', array(
      'check_permissions' => 0,
      'contact_id'        => $organisation_id,
      'location_type_id'  => $location_type_id));
    if ($existing_org_addresses['count'] <= 0) {
      // Organisation does not have a WORK address.
      return FALSE;
    }

    // Check whether contact already has a WORK address.
    $existing_contact_addresses = civicrm_api3('Address', 'get', array(
        'check_permissions' => 0,
        'contact_id'        => $contact_id,
      'location_type_id'    => $location_type_id));
    if ($existing_contact_addresses['count'] > 0) {
      // Contact already has a WORK address.
      return FALSE;
    }

    // Create a shared address.
    $address = reset($existing_org_addresses['values']);
    $address['contact_id']         = $contact_id;
    $address['master_id']          = $address['id'];
    $address['check_permissions']  = 0;
    unset($address['id']);
    civicrm_api3('Address', 'create', $address);
    return TRUE;
  }

}
