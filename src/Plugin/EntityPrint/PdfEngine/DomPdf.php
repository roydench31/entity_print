<?php

/**
 * @file
 * Contains \Drupal\entity_print\Plugin\EntityPrint\PdfEngine\DomPdf.
 */

namespace Drupal\entity_print\Plugin\EntityPrint\PdfEngine;

use Dompdf\Dompdf as DompdfLib;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_print\Plugin\PdfEngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PluginID("dompdf")
 *
 * To use this implementation you will need the DomPDF library, simply run
 *
 * @code
 * composer require "dompdf/dompdf:0.7.0-beta3"
 * @endcode
 */
class DomPdf extends PluginBase implements PdfEngineInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Dompdf\Dompdf
   */
  protected $pdf;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // @TODO, wire into $configuration. @see https://drupal.org/node/2663790
    $this->pdf = new DompdfLib([
      'enable_html5_parser' => TRUE,
      'enable_remote' => TRUE,
    ]);

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
    if ($this->getError()) {
      return FALSE;
    }

    $this->pdf->stream($filename);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    global $_dompdf_warnings;
    if (is_array($_dompdf_warnings)) {
      return implode(', ', $_dompdf_warnings);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance() {
    return $this->pdf;
  }

}
