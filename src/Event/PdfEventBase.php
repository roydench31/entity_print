<?php

namespace Drupal\entity_print\Event;

use Drupal\entity_print\Plugin\PdfEngineInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class PdfEventBase extends Event {

  /**
   * @var \Drupal\entity_print\Plugin\PdfEngineInterface
   */
  protected $pdfEngine;

  /**
   * The PDF Engine event base class.
   *
   * @param \Drupal\entity_print\Plugin\PdfEngineInterface $pdf_engine
   *   The PDF Engine.
   */
  public function __construct(PdfEngineInterface $pdf_engine) {
    $this->pdfEngine = $pdf_engine;
  }

  /**
   * Gets the PDF Engine plugin that will print the PDF.
   *
   * @return \Drupal\entity_print\Plugin\PdfEngineInterface
   *   The PDF Engine.
   */
  public function getPdfEngine() {
    return $this->pdfEngine;
  }

}
