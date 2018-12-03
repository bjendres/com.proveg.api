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

use CRM_ProvegAPI_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_ProvegAPI_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm() {

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



    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    $current_values = CRM_Core_BAO_Setting::getItem('com.proveg.api', 'pvapi_config');
    $this->setDefaults($current_values);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
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
}
