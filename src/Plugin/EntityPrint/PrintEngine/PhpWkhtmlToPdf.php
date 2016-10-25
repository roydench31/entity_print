<?php

namespace Drupal\entity_print\Plugin\EntityPrint\PrintEngine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_print\Plugin\ExportTypeInterface;
use Drupal\entity_print\PrintEngineException;
use Drupal\entity_print\Plugin\PrintEngineBase;
use mikehaertl\wkhtmlto\Pdf;

/**
 * @PrintEngine(
 *   id = "phpwkhtmltopdf",
 *   label = @Translation("Php Wkhtmltopdf"),
 *   export_type = "pdf"
 * )
 *
 * To use this implementation you will need the DomPDF library, simply run:
 *
 * @code
 *     composer require "mikehaertl/phpwkhtmltopdf ~2.1"
 * @endcode
 */
class PhpWkhtmlToPdf extends PrintEngineBase {

  /**
   * @var \mikehaertl\wkhtmlto\Pdf
   */
  protected $print;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExportTypeInterface $export_type) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $export_type);
    $this->print = new Pdf([
      'binary' => $this->configuration['binary_location'],
      'orientation' => $this->configuration['orientation'],
      'username' => $this->configuration['username'],
      'password' => $this->configuration['password'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getInstallationInstructions() {
    return t('Please install with: @command', ['@command' => 'composer require "mikehaertl/phpwkhtmltopdf ~2.1"']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'binary_location' => '/usr/local/bin/wkhtmltopdf',
      'orientation' => 'portrait',
      'username' => '',
      'password' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Paper Orientation'),
      '#options' => [
        static::PORTRAIT => $this->t('Portrait'),
        static::LANDSCAPE => $this->t('Landscape'),
      ],
      '#description' => $this->t('The paper orientation one of Landscape or Portrait'),
      '#default_value' => $this->configuration['orientation'],
    ];
    $form['binary_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Binary Location'),
      '#description' => $this->t('Set this to the system path where the PDF engine binary is located.'),
      '#default_value' => $this->configuration['binary_location'],
    ];
    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('HTTP Authentication'),
      '#open' => !empty($this->configuration['username']) || !empty($this->configuration['password']),
    ];
    $form['credentials']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('If your website is behind HTTP Authentication you can set the username'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['credentials']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('If your website is behind HTTP Authentication you can set the password'),
      '#default_value' => $this->configuration['password'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $binary_location = $form_state->getValue('binary_location');
    if (!file_exists($binary_location)) {
      $form_state->setErrorByName('binary_location', sprintf('The wkhtmltopdf binary does not exist at %s', $binary_location));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function send($filename = NULL) {
    // If the filename received here is NULL, force open in the browser
    // otherwise attempt to have it downloaded.
    if (!$this->print->send($filename, !(bool) $filename)) {
      throw new PrintEngineException(sprintf('Failed to generate PDF: %s', $this->print->getError()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($content) {
    $this->print->addPage($content);
  }

  /**
   * {@inheritdoc}
   */
  public static function dependenciesAvailable() {
    return class_exists('mikehaertl\wkhtmlto\Pdf') && !drupal_valid_test_ua();
  }

}
