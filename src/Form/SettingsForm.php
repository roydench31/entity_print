<?php

/**
 * @file
 * Contains \Drupal\entity_print\Form\SettingsForm.
 */

namespace Drupal\entity_print\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

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

    /** @var \Drupal\entity_print\Plugin\EntityPrintPluginManager $pluginManager */
    $pluginManager = \Drupal::service('plugin.manager.entity_print.pdf_engine');
    $pdf_plugins = array_keys($pluginManager->getDefinitions());
    $pdf_engines = array_combine($pdf_plugins, $pdf_plugins);

    $config = $this->config('entity_print.settings');
    $form['default_css'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Default CSS'),
      '#description' => t('Provides some very basic font and padding styles.'),
      '#default_value' => $config->get('default_css'),
    ];
    $form['force_download'] = [
      '#type' => 'checkbox',
      '#title' => t('Force Download'),
      '#description' => t('This option will attempt to force the browser to download the PDF with a filename from the node title.'),
      '#default_value' => $config->get('force_download'),
    ];
    $form['binary_location'] = [
      '#type' => 'textfield',
      '#title' => t('Binary Location'),
      '#description' => t('Set this to the system path where the PDF engine binary is located.'),
      '#default_value' => $config->get('binary_location'),
    ];
    $form['pdf_engine'] = [
      '#type' => 'select',
      '#title' => t('Pdf Engine'),
      '#description' => 'Select the PDF engine to render the PDF',
      '#options' => $pdf_engines,
      '#default_value' => '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('entity_print.settings')
      ->set('default_css', $values['default_css'])
      ->set('force_download', $values['force_download'])
      ->set('binary_location', $values['binary_location'])
      ->set('pdf_engine', $values['pdf_engine'])
      ->save();
  }

}
