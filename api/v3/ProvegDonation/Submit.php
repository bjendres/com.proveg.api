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

  $extra_return_values = array();

  try {
    if ($params['frequency'] && $params['payment_instrument_id'] != 'sepa') {
      throw new CiviCRM_API3_Exception(
        'Recurring donations can only be submitted with SEPA.',
        'invalid_format'
      );
    }

    // Get the ID of the contact matching the given contact data, or create a
    // new contact if none exists for the given contact data.
    $contact_data = array(
      'first_name' => $params['first_name'],
      'last_name' => $params['last_name'],
      'email' => $params['email'],
      'street_address' => $params['street_address'],
      'city' => $params['city'],
      'postal_code' => $params['postal_code'],
      'country' => $params['country'],
    );
    // Determine gender ID from the given gender.
    if (!empty($params['gender'])) {
      $gender_options = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'gender'));
      $genders = array();
      foreach ($gender_options['values'] as $gender_option) {
        $genders[$gender_option['value']] = $gender_option['name'];
      }
      switch ($params['gender']) {
        case 'm':
          $contact_data['gender_id'] = array_search('Male', $genders);
          break;
        case 'f':
          $contact_data['gender_id'] = array_search('Female', $genders);
          break;
        default:
          throw new CiviCRM_API3_Exception('Could not determine option value from given gender.', 0);
          break;
      }
    }

    if (!$contact_id = CRM_ProvegAPI_Submission::getContact('Individual', $contact_data)) {
      throw new CiviCRM_API3_Exception(
        'Individual contact could not be found or created.',
        'invalid_format'
      );
    }

    // Prepare contribution data.
    $contribution_data = array(
      'financial_type_id' => CRM_ProvegAPI_Submission::FINANCIAL_TYPE_ID,
      'contact_id' => $contact_id,
      'total_amount' => $params['amount'] / 100,
      'source' => (!empty($params['contribution_source']) ? $params['contribution_source'] : CRM_ProvegAPI_Submission::CONTRIBUTION_SOURCE_DEFAULT),
      'receive_date' => date('YmdHis', (!empty($params['receive_date']) ? $params['receive_date'] : REQUEST_TIME)),
    );

    // Handle recurring donations.
    if ($params['frequency']) {
      $contribution_data['frequency_unit'] = 'month';
      $contribution_data['frequency_interval'] = 12 / $params['frequency'];
      $contribution_data['amount'] = $contribution_data['total_amount'];
      unset($contribution_data['total_amount']);
    }

    // Handle payment instruments.
    switch ($params['payment_instrument_id']) {
      // SEPA.
      case 'sepa':
        // Require IBAN.
        if (empty($params['iban'])) {
          throw new CiviCRM_API3_Exception(
            'For donations via SEPA, the IBAN must be provided.',
            'invalid_format'
          );
        }
        elseif ($error = CRM_Sepa_Logic_Verification::verifyIBAN($params['iban'])) {
          throw new CiviCRM_API3_Exception(
            $error,
            'invalid_format'
          );
        }
        // Require BIC.
        if (empty($params['bic'])) {
          throw new CiviCRM_API3_Exception(
            'For donations via SEPA, the SWIFT code (BIC) must be provided.',
            'invalid_format'
          );
        }
        elseif ($error = CRM_Sepa_Logic_Verification::verifyBIC($params['bic'])) {
          throw new CiviCRM_API3_Exception(
            $error,
            'invalid_format'
          );
        }

        // Create SEPA mandate and contribution.
        $contribution_data['type'] = ($params['frequency'] ? 'RCUR' : 'OOFF');
        $contribution_data['iban'] = $params['iban'];
        $contribution_data['bic'] = $params['bic'];
        $sepa_mandate = civicrm_api3(
          'SepaMandate',
          'createfull',
          $contribution_data
        );
        if (!empty($result['is_error'])) {
          throw new CiviCRM_API3_Exception(
            'Could not create a SEPA mandate and/or contribution.',
            'invalid_format',
            array(
              'result' => $sepa_mandate,
            )
          );
        }
        $extra_return_values['SepaMandate'] = $sepa_mandate;

        // Load contribution.
        if (!empty($sepa_mandate['values'][$sepa_mandate['id']]['entity_id'])) {
          if ($sepa_mandate['values'][$sepa_mandate['id']]['entity_table'] == 'civicrm_contribution') {
            $contribution = civicrm_api3('Contribution','get', array(
              'id' => $sepa_mandate['values'][$sepa_mandate['id']]['entity_id']
            ));
          } else if ($sepa_mandate['values'][$sepa_mandate['id']]['entity_table'] == 'civicrm_contribution_recur') {
            $contribution = civicrm_api3('ContributionRecur','get', array(
              'id' => $sepa_mandate['values'][$sepa_mandate['id']]['entity_id']));
          }
          if (!isset($contribution)) {
            throw new CiviCRM_API3_Exception(
              'Could not load contribution for SEPA mandate.',
              'invalid_format'
            );
          }
          $contribution = $contribution['values'];
        }
        break;

      // PayPal.
      case 'paypal':
        $contribution_data['payment_instrument_id'] = CRM_ProvegAPI_Submission::PAYMENT_INSTRUMENT_ID_PAYPAL;
        $contribution_data['contribution_status_id'] = 'Completed';
        $contribution = civicrm_api3(
          'Contribution',
          'create',
          $contribution_data
        );
        break;

      // Invalid payment method.
      default:
        throw new CiviCRM_API3_Exception(
          'Invalid payment instrument.',
          'invalid_format'
        );
        break;
    }

    // If requested, create membership for the contact.
    if (!empty($params['membership_type_id'])) {
      // Require membership sub type ID.
      if (empty($params['membership_subtype_id'])) {
        throw new CiviCRM_API3_Exception('For memberships, the membership sub type must be provided.', 'invalid_format');
      }

      $membership_data = array(
        'membership_type_id' => $params['membership_type_id'],
        'custom_' . CRM_ProvegAPI_Submission::MEMBERSHIP_SUB_TYPE_FIELD_ID => $params['membership_subtype_id'],
        'contact_id' => $contact_id,
      );
      // TODO: Add Foreign key to recurring contribution (if it's recurring)?
      // $membership_data['contribution_recur_id'] = $contribution['id'];

      $membership = civicrm_api3('Membership', 'create', $membership_data);

      // Include membership in extraReturnValues parameter.
      $extra_return_values['Membership'] = $membership;
    }

    // If requested, perform a newsletter subscription for the contact.
    if (!empty($params['newsletter'])) {
      $newsletter_subscription = civicrm_api3('ProvegNewsletterSubscription', 'submit', array(
        'contact_id' => $contact_id,
        'newsletter' => 1,
      ));
      $extra_return_values['ProvegNewsletterSubscription'] = $newsletter_subscription;
    }

    return civicrm_api3_create_success($contribution, $params, NULL, NULL, $dao = NULL, array('extra' => $extra_return_values));
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
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The ID of the membership type to assign to the contact.',
  );
  $params['membership_subtype_id'] = array(
    'name' => 'membership_subtype_id',
    'title' => 'Membership sub type',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The ID of the membership sub type to assign to the contact.',
  );
  $params['amount'] = array(
    'name'         => 'amount',
    'title'        => 'Amount (in Euro cents)',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => 'The donation amount in Euro cents.',
  );
  $params['frequency'] = array(
    'name'         => 'frequency',
    'title'        => 'Frequency',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
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
    'api.required' => 1,
    'description'  => 'The contact\'s first name.',
  );
  $params['last_name'] = array(
    'name'         => 'last_name',
    'title'        => 'Last Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s last name.',
  );
  $params['email'] = array(
    'name'         => 'email',
    'title'        => 'Email',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s email.',
  );
  $params['street_address'] = array(
    'name'         => 'street_address',
    'title'        => 'Street address',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s street address.',
  );
  $params['postal_code'] = array(
    'name'         => 'postal_code',
    'title'        => 'Postal / ZIP code',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s postal code.',
  );
  $params['city'] = array(
    'name'         => 'city',
    'title'        => 'City',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s city.',
  );
  $params['country'] = array(
    'name'         => 'country',
    'title'        => 'Country',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s country.',
  );
  $params['payment_instrument_id'] = array(
    'name'         => 'payment_instrument_id',
    'title'        => 'Payment instrument',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
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
  $params['receive_date'] = array(
    'name'         => 'receive_date',
    'title'        => 'Receive date',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description'  => 'A timestamp when the donation was issued.',
  );
  $params['contribution_source'] = array(
    'name' => 'contribution_source',
    'title' => 'Contribution source',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'Text to identify the origin of the contribution.',
  );
}
