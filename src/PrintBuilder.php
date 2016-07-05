<?php

namespace Drupal\entity_print;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Event\PreSendPrintEvent;
use Drupal\entity_print\Event\PreSendPrintMultipleEvent;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\entity_print\Renderer\RendererFactoryInterface;
use Drupal\entity_print\Renderer\RendererInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PrintBuilder implements PrintBuilderInterface {

  /**
   * The Print Renderer factory.
   *
   * @var \Drupal\entity_print\Renderer\RendererFactoryInterface
   */
  protected $rendererFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs a new EntityPrintPrintBuilder.
   *
   * @param \Drupal\entity_print\Renderer\RendererFactoryInterface $renderer_factory
   *   The Renderer factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(RendererFactoryInterface $renderer_factory, EventDispatcherInterface $event_dispatcher) {
    $this->rendererFactory = $renderer_factory;
    $this->dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function printSingle(EntityInterface $entity, PrintEngineInterface $print_engine, $force_download = FALSE, $use_default_css = TRUE) {
    $renderer = $this->rendererFactory->create($entity);
    $print_engine->addPage($renderer->getHtml($entity, $use_default_css, TRUE));

    // Allow other modules to alter the generated Print object.
    $this->dispatcher->dispatch(PrintEvents::PRE_SEND, new PreSendPrintEvent($print_engine, $entity));

    // If we're forcing a download we need a filename otherwise it's just sent
    // straight to the browser.
    // @TODO, abstract the file extension. https://www.drupal.org/node/2760203.
    $filename = $force_download ? $renderer->getFilename($entity) . '.pdf' : NULL;

    return $print_engine->send($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function printMultiple(array $entities, PrintEngineInterface $print_engine, $force_download = FALSE, $use_default_css = TRUE) {
    $renderer = $this->rendererFactory->create($entities);
    $print_engine->addPage($renderer->getHtmlMultiple($entities, $use_default_css, TRUE));

    // Allow other modules to alter the generated Print object.
    $this->dispatcher->dispatch(PrintEvents::PRE_SEND_MULTIPLE, new PreSendPrintMultipleEvent($print_engine, $entities));

    // If we're forcing a download we need a filename otherwise it's just sent
    // straight to the browser.
    $filename = $force_download ? $this->generateMultiFilename($entities, $renderer) : NULL;

    return $print_engine->send($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function printHtml(EntityInterface $entity, $use_default_css = TRUE, $optimize_css = TRUE) {
    return $this->rendererFactory->create($entity)->getHtml($entity, $use_default_css, $optimize_css);
  }

  /**
   * Generate a filename when you have multiple entities.
   *
   * @param array $entities
   *   An array of entities to derive the filename for.
   *
   * @return string
   *   The filename to use.
   */
  protected function generateMultiFilename(array $entities, RendererInterface $renderer) {
    $filenames = [];
    foreach ($entities as $entity) {
      $filenames[] = $renderer->getFilename($entity);
    }
    // @TODO, abstract out export type.
    return implode('-', $filenames) . '.pdf';
  }

}
