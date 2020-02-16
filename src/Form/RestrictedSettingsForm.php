<?php

namespace Drupal\negnet_utility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Restricted Access form.
 */
class RestrictedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restricted_access_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'postage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('negnet_utility.restricted_access');

    $types = [];
    foreach (\Drupal::service('entity_type.bundle.info')->getBundleInfo('node') as $m => $t) {
      $types[$m] = $t['label'];
    }

    $form['bundles_enabled'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select which node types can have restricted access'),
      '#multiple' => TRUE,
      '#options' => $types,
      '#default_value' => $config->get('bundles_enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration.
    $this->configFactory->getEditable('negnet_utility.restricted_access')
      // Set the submitted configuration setting.
      ->set('bundles_enabled', $form_state->getValue('bundles_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
