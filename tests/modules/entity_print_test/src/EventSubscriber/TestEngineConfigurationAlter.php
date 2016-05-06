<?php

namespace Drupal\entity_print_test\EventSubscriber;

use Drupal\entity_print\Event\PdfEngineEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * The TestEngineConfigurationAlter class.
 */
class TestEngineConfigurationAlter implements EventSubscriberInterface {

  /**
   * Alter the configuration for our testpdf engine.
   *
   * @param \Symfony\Component\EventDispatcher\GenericEvent $event
   *   The event object.
   */
  public function alterConfiguration(GenericEvent $event) {
    if ($event->getArgument('config')->id() === 'testpdfengine') {
      $event->setArgument('configuration', ['test_engine_suffix' => 'overridden'] + $event->getArgument('configuration'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PdfEngineEvents::CONFIGURATION_ALTER => 'alterConfiguration'
    ];
  }

}
