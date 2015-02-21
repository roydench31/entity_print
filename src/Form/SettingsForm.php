<?php

/**
 * @file
 * Contains \Drupal\entity_print\Form\SettingsForm.
 */

namespace Drupal\entity_print\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures Entity Print settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'entity_print_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_print.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $config = $this->config('entity_print.settings');
    $form['default_css'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Default CSS'),
      '#description' => t('Provides some very basic font and padding styles.'),
      '#default_value' => $config->get('default_css'),
    );
    $form['wkhtmltopdf_location'] = array(
      '#type' => 'textfield',
      '#title' => t('WkhtmlToPdf Location'),
      '#description' => t('Set this to the system path where WkhtmlToPdf is located.'),
      '#default_value' => $config->get('wkhtmltopdf_location'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('entity_print.settings')
      ->set('default_css', $values['default_css'])
      ->set('wkhtmltopdf_location', $values['wkhtmltopdf_location'])
      ->save();
  }

}
