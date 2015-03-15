<?php

/**
 * @file
 * Contains \Drupal\entity_print\Plugin\EntityPrint\PdfEngine\PhpWkhtmlToPdf
 */

namespace Drupal\entity_print\Plugin\EntityPrint\PdfEngine;

use Drupal\entity_print\Plugin\PdfEngineInterface;
use Drupal\Component\Plugin\PluginBase;
use mikehaertl\wkhtmlto\Pdf;

/**
 * @PluginID("phpwkhtmltopdf")
 */
class PhpWkhtmlToPdf extends PluginBase implements PdfEngineInterface {

  /**
   * @var \mikehaertl\wkhtmlto\Pdf
   */
  protected $pdf;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pdf = new Pdf(['binary' => $configuration['binary_location']]);
  }

  /**
   * {@inheritdoc}
   */
  public function send($filename = NULL) {
    return $this->pdf->send($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return $this->pdf->getError();
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($content) {
    $this->pdf->addPage($content);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance() {
    return $this->pdf;
  }

}
