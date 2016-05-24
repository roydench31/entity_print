<?php

namespace Drupal\entity_print\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Plugin\PdfEngineInterface;

/**
 * The PreSendPdfEvent class.
 */
class PreSendPdfEvent extends PdfEventBase {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * PreSendPdfEvent constructor.
   *
   * @param \Drupal\entity_print\Plugin\PdfEngineInterface $pdf_engine
   *   The PDF Engine.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to print.
   */
  public function __construct(PdfEngineInterface $pdf_engine, EntityInterface $entity) {
    parent::__construct($pdf_engine);
    $this->entity = $entity;
  }

  /**
   * Gets the entity that is being printed to PDF.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The content entity.
   */
  public function getEntity() {
    return $this->entity;
  }

}
