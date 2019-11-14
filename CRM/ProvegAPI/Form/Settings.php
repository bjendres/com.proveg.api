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

use CRM_ProvegAPI_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_ProvegAPI_Form_Settings extends CRM_Core_Form {

  const HASH_LINK_COUNT = 10;

  public function buildQuickForm() {

    // Generic settings
    $this->add(
        'checkbox',
        'log_api_calls',
        E::ts('Log all API calls')
    );

    // Configuration for Mailing Self-Service
    $this->add(
        'select',
        'selfservice_xcm_profile',
        E::ts('XCM Profile'),
        CRM_Xcm_Configuration::getProfileList(),
        TRUE
    );

    // Configuration Self-Service link request
    $templates = $this->getMessageTemplates();
    $this->add(
        'select',
        'selfservice_link_request_template_contact_known',
        E::ts('E-Mail Template for Case: Email is known'),
        $templates,
        FALSE
    );
    $this->add(
        'select',
        'selfservice_link_request_template_contact_unknown',
        E::ts('E-Mail Template for Case: Email is <i>not</i> known'),
        $templates,
        FALSE
    );
    $this->add(
        'select',
        'selfservice_link_request_template_contact_ambiguous',
        E::ts('E-Mail Template for Case: Email is <i>ambiguous</i>'),
        $templates,
        FALSE
    );
    $this->add(
        'text',
        'selfservice_link_request_sender',
        E::ts('Sender E-Mail'),
        ['class' => 'huge'],
        TRUE
    );

    // Configuration for hash links (personalised links)
    $hash_link_ids = range(1, self::HASH_LINK_COUNT);
    $this->assign("hash_links", $hash_link_ids);
    foreach ($hash_link_ids as $i) {
      $this->add(
          'text',
          "hash_link_{$i}",
          E::ts('Token Key'),
          ['placeholder' => E::ts("Enter key to activate")]
      );
      $this->addRule("hash_link_{$i}", E::ts("The name mustn't contain special characters or spaces."), 'alphanumeric');

      $this->add(
          'text',
          "hash_link_name_{$i}",
          E::ts('Token Label'),
          ['class' => 'big']
      );
      $this->add(
          'textarea',
          "hash_link_html_{$i}",
          E::ts('Link HTML'),
          ['class' => 'big']
      );
      $this->add(
          'textarea',
          "hash_link_fallback_html_{$i}",
          E::ts('Fallback HTML'),
          ['class' => 'big']
      );
    }



    /** Configuration for Donation API (discontinued)
    // add form elements
    $this->add(
      'select',
      'financial_type_id',
      E::ts('Financial Type for Donations'),
      $this->getList('FinancialType', 'id', 'name'),
      TRUE
    );

    $this->add(
        'select',
        'paypal_instrument_id',
        E::ts('Paypal Instrument'),
        $this->getList('OptionValue', 'value', 'name', ['option_group_id' => 'payment_instrument']),
        TRUE
    );

    $this->add(
        'text',
        'contribution_source_default',
        E::ts('Default Contribution Source'),
        [],
        TRUE
    );

    $this->add(
        'select',
        'sepa_creditor_id',
        E::ts('SEPA Creditor'),
        $this->getList('SepaCreditor', 'id', 'name'),
        TRUE
    );

    $this->add(
        'select',
        'buffer_days',
        E::ts('SEPA Buffer Days'),
        range(0,15),
        TRUE
    );

    $this->add(
        'select',
        'newsletter_group',
        E::ts('Newsletter Group'),
        $this->getList('Group', 'id', 'name'),
        TRUE
    );

    $this->add(
        'select',
        'work_location_type_id',
        E::ts('Location Type for WORK Address'),
        $this->getList('Location Type', 'id', 'name'),
        TRUE
    );

    */


    $this->addButtons([
        [
            'type'      => 'submit',
            'name'      => E::ts('Save'),
            'isDefault' => TRUE,
        ],
    ]);

    // add basic settings
    $current_values = CRM_Core_BAO_Setting::getItem('com.proveg.api', 'pvapi_config');
    if (is_array($current_values)) {
      unset($current_values['qfKey'], $current_values['entryURL']);
      $this->setDefaults($current_values);
    }

    // add hash link specs
    $link_specs = CRM_ProvegAPI_HashLinks::getLinks();
    foreach (range(1, self::HASH_LINK_COUNT) as $i) {
      if (isset($link_specs[$i - 1])) {
        $spec = $link_specs[$i - 1];
        $this->setDefaults([
            "hash_link_{$i}"               => CRM_Utils_Array::value('name', $spec, ''),
            "hash_link_name_{$i}"          => CRM_Utils_Array::value('label', $spec, ''),
            "hash_link_html_{$i}"          => CRM_Utils_Array::value('link_html', $spec, ''),
            "hash_link_fallback_html_{$i}" => CRM_Utils_Array::value('fallback_html', $spec, ''),
        ]);
      }
    }

    parent::buildQuickForm();
  }



  public function postProcess()
  {
    $values = $this->exportValues(null, true);
    unset($values['qfKey'], $values['entryURL']);

    // extract and store hash link specs
    $hash_link_specs = [];
    foreach (range(1, self::HASH_LINK_COUNT) as $i) {
      if (!empty($values["hash_link_{$i}"])) {
        $hash_link_specs[] = [
            'name'          => $values["hash_link_{$i}"],
            'label'         => CRM_Utils_Array::value("hash_link_name_{$i}", $values, $values["hash_link_{$i}"]),
            'link_html'     => html_entity_decode($values["hash_link_html_{$i}"]),
            'fallback_html' => html_entity_decode($values["hash_link_fallback_html_{$i}"])
        ];
      }
      unset($values["hash_link_{$i}"], $values["hash_link_name_{$i}"], $values["hash_link_html_{$i}"], $values["hash_link_fallback_html_{$i}"]);
    }
    Civi::settings()->set('proveg_personalised_links', $hash_link_specs);

    // format + store the rest
    $values['selfservice_link_request_sender'] = html_entity_decode($values['selfservice_link_request_sender']);
    CRM_Core_BAO_Setting::setItem($values, 'com.proveg.api', 'pvapi_config');
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Generate a dropdown list of arbitrary entites
   *
   * @param $entity       string entity name
   * @param $id_field     string name of the field to be used as ID
   * @param $label_field  string name of the field to be used as label
   * @param $params       array  additional parameters for the query
   *
   * @return array list
   * @throws CiviCRM_API3_Exception
   */
  protected function getList($entity, $id_field = 'id', $label_field = 'name', $params = []) {
    if (empty($params['option.limit'])) {
      $params['option.limit'] = 0;
    }

    $list = [];
    $params['return'] = "{$id_field},{$label_field}";
    $results = civicrm_api3($entity, 'get', $params);
    foreach ($results['values'] as $key => $entry) {
      $list[$entry[$id_field]] = $entry[$label_field];
    }
    return $list;
  }

  /**
   * Get a list of eligible message templates
   *
   * @return array
   */
  protected function getMessageTemplates() {
    $templates = ['' => E::ts("disabled")];
    $query = civicrm_api3('MessageTemplate', 'get', [
        'option.limit' => 0,
        'is_active'    => 1,
        'workflow_id'  => ['IS NULL' => 1],
        'return'       => 'id,msg_title'
    ]);
    foreach ($query['values'] as $template) {
      $templates[$template['id']] = $template['msg_title'];
    }
    return $templates;
  }
}
