<?php
use CRM_ProvegAPI_ExtensionUtil as E;

/**
 * ProvegMailing.Subscribe API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_proveg_mailing_Subscribe_spec(&$spec) {
  $spec['email']['api.required'] = 1;
  $spec['first_name']['api.required'] = 0;
  $spec['last_name']['api.required'] = 0;
  $spec['prefix_id']['api.required'] = 0;
  $spec['group_id']['api.required'] = 0;
}

/**
 * ProvegMailing.Subscribe API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_proveg_mailing_Subscribe($params) {

  if (TRUE) {
    return civicrm_api3_create_success('Is all good');
  }
  else {
    throw new API_Exception(/*errorMessage*/ 'Everyone knows that the magicword is "sesame"', /*errorCode*/ 1234);
  }
}
