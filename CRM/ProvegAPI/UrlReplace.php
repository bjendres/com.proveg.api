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


class CRM_ProvegAPI_UrlReplace {

  private static $unsubscribe_url = NULL;
  private static $confirm_url = NULL;

  public function __construct() {
    if ($this::$unsubscribe_url === NULL || $this::$confirm_url === NULL) {
      $this::$unsubscribe_url = CRM_ProvegAPI_Configuration::getSetting('mailing_unsubscription_endpoint');
      $this::$confirm_url     = CRM_ProvegAPI_Configuration::getSetting('mailing_confirmation_endpoint');
    }
  }

  public function parse()

}
