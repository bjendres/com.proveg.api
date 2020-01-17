<?php
/*------------------------------------------------------------+
| ProVeg API extension                                        |
| Copyright (C) 2018 SYSTOPIA                                 |
| Author: P. Batroff (batroff@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/


class CRM_ProvegAPI_MailingSubscribe {

  private $first_name = NULL;
  private $last_name = NULL;
  private $email = NULL;
  private $prefix_id = NULL;
  private $group_id = NULL;

  public function __construct() {
  }

  public function handle_request($parameters) {
    $this->handle_parameters($parameters);
    $contact_id = $this->get_contact();
  }

  private function handle_parameters($parameters) {
    if (isset($parameters['first_name'])) {
      $this->first_name = $parameters['first_name'];
    }
    if (isset($parameters['last_name'])) {
      $this->last_name = $parameters['last_name'];
    }
    if (isset($parameters['email'])) {
      $this->email = $parameters['email'];
    }
    if (isset($parameters['prefix_id'])) {
      $this->prefix_id = $parameters['prefix_id'];
    }
    if (isset($parameters['group_id'])) {
      $this->group_id = $parameters['group_id'];
    } else {
      // get from Config
      $this->group_id = CRM_ProvegAPI_Configuration::getSetting('mailing_default_group_id');
    }
  }

  private function get_contact() {
    $xcm_profile = CRM_ProvegAPI_Configuration:;getSetting('mailing_xcm_profile');
    $contact_type = 'Individual';
    $result = civicrm_api3('Contact', 'getorcreate', [
      'contact_type' => $contact_type,
      'xcm_profile' => $xcm_profile,
      'prefix_id' => $this->prefix_id,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'email' => $this->email,
    ]);
    return $result['id'];
  }

}
