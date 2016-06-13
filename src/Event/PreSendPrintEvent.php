<?php

namespace Drupal\entity_print\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;

/**
 * The PreSendPrintEvent class.
 */
class PreSendPrintEvent extends PrintEventBase {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * PreSendPrintEvent constructor.
   *
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The Print Engine.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to print.
   */
  public function __construct(PrintEngineInterface $print_engine, EntityInterface $entity) {
    parent::__construct($print_engine);
    $this->entity = $entity;
  }

  /**
   * Gets the entity that is being printed to Print.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The content entity.
   */
  public function getEntity() {
    return $this->entity;
  }

}
