<?php

namespace Drupal\entity_print\Event;

use Drupal\entity_print\Plugin\PdfEngineInterface;

/**
 * The PreSendPdfMultipleEvent class.
 */
class PreSendPdfMultipleEvent extends PdfEventBase {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entities;

  /**
   * PreSendPdfEvent constructor.
   *
   * @param \Drupal\entity_print\Plugin\PdfEngineInterface $pdf_engine
   *   The PDF Engine.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to print.
   */
  public function __construct(PdfEngineInterface $pdf_engine, array $entities) {
    parent::__construct($pdf_engine);
    $this->entities = $entities;
  }

  /**
   * Gets the entities being printed to PDF.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The content entities.
   */
  public function getEntities() {
    return $this->entities;
  }

}
