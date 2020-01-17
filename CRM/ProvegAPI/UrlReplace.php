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
  private static $baseurl = NULL;

  public function __construct() {
    if ($this::$unsubscribe_url === NULL || $this::$confirm_url === NULL) {
      $this::$unsubscribe_url = CRM_ProvegAPI_Configuration::getSetting('mailing_unsubscription_endpoint');
      $this::$confirm_url     = CRM_ProvegAPI_Configuration::getSetting('mailing_confirmation_endpoint');
    }
    if ($this::$baseurl === NULL) {
      $this::$baseurl = CIVICRM_UF_BASEURL;
    }
  }

  /**
   * Parse mail content and replace URLs
   * @param $content
   */
  public function parse(&$content) {
    $this->parse_confirmation_endpoint($content);
    $this->parse_unsubscription_endpoint($content);
  }

  /**
   * Replace confirmation endpoint URL to configured URL
   * @param $content
   */
  private function parse_confirmation_endpoint(&$content) {
    $pattern = "/https:\/\/(?P<url>[a-zA-Z0-9\/_.-]+)\/civicrm\/mailing\/confirm/";
    preg_replace($pattern, $this::$confirm_url, $content);
  }

  /**
   * Replace unsubscribe URL to configured URL
   * @param $content
   */
  private function parse_unsubscription_endpoint(&$content) {
    $pattern = "/https:\/\/(?P<url>[a-zA-Z0-9\/_.-]+)\/civicrm\/mailing\/unsubscribe/";
    preg_replace($pattern, $this::$confirm_url, $content);
  }

}
