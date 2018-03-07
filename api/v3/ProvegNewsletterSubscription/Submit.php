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
 * Submit a newsletter subscription.
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
function civicrm_api3_proveg_newsletter_subscription_submit($params) {
  // Log the API call to the CiviCRM debug log.
  if (defined('PROVEG_API_LOGGING') && PROVEG_API_LOGGING) {
    CRM_Core_Error::debug_log_message('ProvegNewsletterSubscription.submit: ' . json_encode($params));
  }

  try {
    if (!empty($params['contact_id'])) {
      $contact_id = $params['contact_id'];
    }
    elseif (empty($params['email'])) {
      throw new CiviCRM_API3_Exception(
        'Mandatory key(s) missing from params array: email',
        'mandatory_missing',
        array(
          'fields' => array('email'),
          'entity' => 'ProvegNewsletterSubscription',
          'action' => 'submit',
        )
      );
    }
    else {
      // Get the ID of the contact matching the given contact data, or create a
      // new contact if none exists for the given contact data.
      $contact_data = array(
        'email' => $params['email'],
      );
      if (!$contact_id = CRM_ProvegAPI_Submission::getContact('Individual', $contact_data)) {
        throw new CiviCRM_API3_Exception('Individual contact could not be found or created.', 'invalid_format');
      }
    }

    $groupcontact = civicrm_api3('GroupContact', 'create', array(
      'group_id' => CRM_ProvegAPI_Submission::NEWSLETTER_GROUP_ID,
      'contact_id' => $contact_id,
      'status' => (!empty($params['newsletter']) ? 'Added' : 'Removed'),
    ));

    return civicrm_api3_create_success($groupcontact, $params, NULL, NULL, $dao = NULL, array());

  }
   catch (CiviCRM_API3_Exception $exception) {
     if (defined('PROVEG_API_LOGGING') && PROVEG_API_LOGGING) {
       CRM_Core_Error::debug_log_message('ProvegNewsletterSubscription:submit:Exception caught: ' . $exception->getMessage());
     }

     $extraParams = $exception->getExtraParams();

     return civicrm_api3_create_error($exception->getMessage(), $extraParams);
   }
}

/**
 * Parameter specification for the "Submit" action on
 * "ProvegNewsletterSubscription" entities.
 *
 * @param $params
 */
function _civicrm_api3_proveg_newsletter_subscription_submit_spec(&$params) {
  $params['email'] = array(
    'name'         => 'email',
    'title'        => 'Email',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s email.',
  );
  $params['newsletter'] = array(
    'name'         => 'newsletter',
    'title'        => 'Newsletter',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => 'Whether to subscribe to or remove the contact from the configured newsletter group.',
  );
  $params['contact_id'] = array(
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'description' => 'The contact\'s ID.',
  );
}
