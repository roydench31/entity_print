<?php

namespace Drupal\entity_print_test\EventSubscriber;

use Drupal\entity_print\Event\PdfCssAlterEvent;
use Drupal\entity_print\Event\PdfEvents;
use Drupal\entity_print\Event\PreSendPdfEvent;
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
   * Alter the CSS renderable array and add our CSS.
   * @param \Drupal\entity_print\Event\PdfCssAlterEvent $event
   *   The event object.
   */
  public function alterCss(PdfCssAlterEvent $event) {
    $event->getBuild()['#attached']['library'][] = 'entity_print_test_theme/module';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PdfEvents::CONFIGURATION_ALTER => 'alterConfiguration',
      PdfEvents::CSS_ALTER => 'alterCss',
    ];
  }

}
