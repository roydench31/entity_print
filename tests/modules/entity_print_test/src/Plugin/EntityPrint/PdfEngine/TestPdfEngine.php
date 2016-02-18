<?php

/**
 * @file
 * Contains \Drupal\entity_print_test\Plugin\EntityPrint\PdfEngine\TestPdfEngine
 */

namespace Drupal\entity_print_test\Plugin\EntityPrint\PdfEngine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_print\PdfEngineException;
use Drupal\entity_print\Plugin\PdfEngineBase;

/**
 * @PdfEngine(
 *   id = "testpdfengine",
 *   label= @Translation("Test PDF Engine")
 * )
 */
class TestPdfEngine extends PdfEngineBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function send($filename = NULL) {
    return 'Using testpdfengine';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['test_engine_setting'] = [
      '#title' => $this->t('Test setting'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['test_engine_setting'],
      '#description' => $this->t('Test setting'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['test_engine_setting'] = $form_state->getValue('test_engine_setting');
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('test_engine_setting') === 'rejected') {
      $form_state->setErrorByName('test_engine_setting', 'Setting has an invalid value');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'test_engine_setting' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {}

  /**
   * {@inheritdoc}
   */
  public function addPage($content) {}

  /**
   * {@inheritdoc}
   */
  public static function dependenciesAvailable() {
    return TRUE;
  }

}
