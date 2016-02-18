<?php

/**
 * @file
 * Contains \Drupal\entity_print\Plugin\EntityPrint\PdfEngine\DomPdf.
 */

namespace Drupal\entity_print\Plugin\EntityPrint\PdfEngine;

use Dompdf\Dompdf as DompdfLib;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_print\PdfEngineException;
use Drupal\entity_print\Plugin\PdfEngineBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PdfEngine(
 *   id = "dompdf",
 *   label = @Translation("Dompdf")
 * )
 *
 * To use this implementation you will need the DomPDF library, simply run
 *
 * @code
 *     composer require "dompdf/dompdf:0.7.0-beta3"
 * @endcode
 */
class DomPdf extends PdfEngineBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Dompdf\Dompdf
   */
  protected $pdf;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pdf = new DompdfLib($this->configuration);
    $this->pdf
      ->setBaseHost($request->getHost())
      ->setProtocol($request->getScheme() . '://');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getInstallationInstructions() {
    return t('Please install with: @command', ['@command' => 'composer require "dompdf/dompdf:0.7.0-beta3"']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enable_html5_parser' => TRUE,
      'enable_remote' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enable_html5_parser'] = [
      '#title' => $this->t('Enable HTML5 Parser'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable_html5_parser'],
      '#description' => $this->t('Note, this library doesn\'t work without this option enabled.'),
    ];
    $form['enable_remote'] = [
      '#title' => $this->t('Enable Remote URLs'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable_remote'],
      '#description' => $this->t('This settings must be enabled for CSS and Images to work unless you manipulate the source manually.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($content) {
    $html = (string) $content;
    $this->pdf->loadHtml($html);
  }

  /**
   * {@inheritdoc}
   */
  public function send($filename = NULL) {
    $this->pdf->render();

    // Dompdf doesn't have a return value for send so just check the error
    // global it provides.
    if ($errors = $this->getError()) {
      throw new PdfEngineException(sprintf('Failed to generate PDF: %s', $errors));
    }

    $this->pdf->stream($filename);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getError() {
    global $_dompdf_warnings;
    if (is_array($_dompdf_warnings)) {
      return implode(', ', $_dompdf_warnings);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function dependenciesAvailable() {
    return class_exists('Dompdf\Dompdf');
  }

}