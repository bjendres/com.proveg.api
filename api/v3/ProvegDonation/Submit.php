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

/**
 * Submit a donation.
 *
 * @param array $params
 *   Associative array of property name/value pairs.
 *
 * @return array api result array
 *
 * @access public
 *
 * @throws \API_Exception
 */
function civicrm_api3_proveg_donation_submit($params) {
  // Log the API call to the CiviCRM debug log.
  if (defined('PROVEG_API_LOGGING') && PROVEG_API_LOGGING) {
    CRM_Core_Error::debug_log_message('ProvegDonation.submit: ' . json_encode($params));
  }

  try {
    // Get the ID of the contact matching the given contact data, or create a
    // new contact if none exists for the given contact data.
    if (!$contact_id = CRM_ProvegAPI_Submission::getContact('Individual', $params)) {
      throw new CiviCRM_API3_Exception('Individual contact could not be found or created.', 'invalid_format');
    }

    // Limit allowed payment instruments.
    if (!in_array($params['payment_instrument_id'], array('paypal', 'sepa'))) {
      throw new CiviCRM_API3_Exception('Unknown payment instrument.', 'invalid_format');
    }

    // Handle SEPA payment method.
    if ($params['payment_instrument_id'] == 'sepa') {
      if (empty($params['iban'])) {
        throw new CiviCRM_API3_Exception('For donations via SEPA, the IBAN must be provided.', 'invalid_format');
      }
      if (empty($params['bic'])) {
        throw new CiviCRM_API3_Exception('For donations via SEPA, the SWIFT code (BIC) must be provided.', 'invalid_format');
      }

      // TODO: Create full SEPA mandate.
    }

    // Create contribution.
    $contribution_data = array(
      'financial_type_id' => CRM_ProvegAPI_Submission::FINANCIAL_TYPE_ID,
      'contact_id' => $contact_id,
      'payment_instrument_id' => $params['payment_instrument_id'],
      'total_amount' => $params['amount'] / 100,
      'contribution_status_id' => 'Completed', // TODO: Change for SEPA.
      'source' => 'ProvegAPI', // TODO: Anything else?
      'receive_date' => date('YmdHis', REQUEST_TIME), // TODO: Maybe use an API parameter?
    );
    $contribution = civicrm_api3('Contribution', 'create', $contribution_data);

    // If requested, add contact to the groups defined in the profile.
    if (!empty($params['newsletter'])) {
      civicrm_api3('GroupContact', 'create', array(
        'group_id' => CRM_ProvegAPI_Submission::NEWSLETTER_GROUP_ID,
        'contact_id' => $contact_id,
      ));
    }

    return civicrm_api3_create_success($contribution, $params, NULL, NULL, $dao = NULL, array());
  }
  catch (CiviCRM_API3_Exception $exception) {
    if (defined('PROVEG_API_LOGGING') && PROVEG_API_LOGGING) {
      CRM_Core_Error::debug_log_message('ProvegDonation:submit:Exception caught: ' . $exception->getMessage());
    }

    $extraParams = $exception->getExtraParams();

    // TODO: Create an activity for failed contributions?
//    // Rollback current base transaction in order to not rollback the creation
//    // of the activity.
//    if (($frame = \Civi\Core\Transaction\Manager::singleton()->getFrame()) !== NULL) {
//      $frame->forceRollback();
//    }
//    try {
//      // Create an activity of type "Failed contribution processing" and assign
//      // it to the contact defined in configuration with fallback to the
//      // currently logged in contact.
//      $assignee_id = CRM_Core_BAO_Setting::getItem(
//        'de.systopia.provegapi',
//        'provegapi_contact_failed_contribution_processing'
//      );
//      $activity_data = array(
//        'assignee_id'        => $assignee_id,
//        'activity_type_id'   => CRM_Core_OptionGroup::getValue('activity_type', 'provegapi_failed_contribution_processing', 'name'),
//        'subject'            => 'Failed ProVeg API contribution processing',
//        'activity_date_time' => date('YmdHis'),
//        'source_contact_id'  => CRM_Core_Session::singleton()->getLoggedInContactID(),
//        'status_id'          => CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name'),
//        'target_id'          => (isset($organisation_id) ? $organisation_id : $contact_id),
//        'details'            => json_encode($params),
//      );
//      $activity = civicrm_api3('Activity', 'create', $activity_data);
//      $extraParams['additional_notices']['activity']['result'] = $activity;
//      if (!isset($assignee_id)) {
//        $extraParams['additional_notices']['activity']['messages'][] = 'No contact ID is configured for assigning an activity of the type "Failed contribution processing". The activity has not been assigned to a contact.';
//      }
//    }
//    catch (CiviCRM_API3_Exception $activity_exception) {
//      $extraParams['additional_notices']['activity']['messages'][] = 'Failed creating an activity of the type "Failed contribution processing".';
//      $extraParams['additional_notices']['activity']['result'] = civicrm_api3_create_error($activity_exception->getMessage(), $activity_exception->getExtraParams());
//    }

    return civicrm_api3_create_error($exception->getMessage(), $extraParams);
  }
}

/**
 * Parameter specification for the "Submit" action on "ProvegDonation" entities.
 *
 * @param $params
 */
function _civicrm_api3_proveg_donation_submit_spec(&$params) {
  $params['membership_type_id'] = array(
    'name' => 'membership_type_id',
    'title' => 'Membership type',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => 'The ID of the membership type to assign to the contact.',
  );
  $params['membership_subtype_id'] = array(
    'name' => 'membership_subtype_id',
    'title' => 'Membership sub type',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => 'The ID of the membership sub type to assign to the contact.',
  );
  $params['amount'] = array(
    'name'         => 'amount',
    'title'        => 'Amount (in Euro cents)',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description'  => 'The donation amount in Euro cents.',
  );
  $params['frequency'] = array(
    'name'         => 'frequency',
    'title'        => 'Frequency',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description'  => 'The number of installments per year, or 0 for one-off.',
  );
  $params['gender'] = array(
    'name'         => 'gender',
    'title'        => 'Gender',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s gender.',
  );
  $params['first_name'] = array(
    'name'         => 'first_name',
    'title'        => 'First Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s first name.',
  );
  $params['last_name'] = array(
    'name'         => 'last_name',
    'title'        => 'Last Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s last name.',
  );
  $params['email'] = array(
    'name'         => 'email',
    'title'        => 'Email',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s email.',
  );
  $params['street_address'] = array(
    'name'         => 'street_address',
    'title'        => 'Street address',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s street address.',
  );
  $params['postal_code'] = array(
    'name'         => 'postal_code',
    'title'        => 'Postal / ZIP code',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s postal code.',
  );
  $params['city'] = array(
    'name'         => 'city',
    'title'        => 'City',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s city.',
  );
  $params['country'] = array(
    'name'         => 'country',
    'title'        => 'Country',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s country.',
  );
  $params['payment_instrument_id'] = array(
    'name'         => 'payment_instrument_id',
    'title'        => 'Payment instrument',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The payment method used for the donation.',
  );
  $params['iban'] = array(
    'name'         => 'iban',
    'title'        => 'IBAN',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The IBAN to register the SEPA mandate for.',
  );
  $params['bic'] = array(
    'name'         => 'bic',
    'title'        => 'BIC',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The SWIFT code (BIC) to register the SEPA mandate for.',
  );
  $params['account_holder'] = array(
    'name'         => 'account_holder',
    'title'        => 'Account holder',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The bank account holder\'s full name (when different from contact).',
  );
  $params['newsletter'] = array(
    'name'         => 'newsletter',
    'title'        => 'Newsletter',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description'  => 'Whether to subscribe the contact to the configured newsletter group.',
  );
}
