<?php

namespace Drupal\heart_custom_entities\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HeartEmailTemplateSettingsForm.
 *
 * Provides a settings form for the Heart Email Template entity.
 */
class HeartEmailTemplateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['heart_custom_entities.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'heart_email_template_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('heart_custom_entities.settings');

    $form['example_setting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example Setting'),
      '#description' => $this->t('An example setting for your custom entity.'),
      '#default_value' => $config->get('example_setting'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('heart_custom_entities.settings')
      ->set('example_setting', $form_state->getValue('example_setting'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}