<?php

namespace Drupal\entity_print;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Event\PdfEvents;
use Drupal\entity_print\Event\PreSendPdfEvent;
use Drupal\entity_print\Event\PreSendPdfMultipleEvent;
use Drupal\entity_print\Plugin\PdfEngineInterface;
use Drupal\entity_print\Renderer\RendererFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityPrintPdfBuilder implements PdfBuilderInterface {

  /**
   * The PDF Renderer factory.
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
   * Constructs a new EntityPrintPdfBuilder.
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
  public function getEntityRenderedAsPdf(EntityInterface $entity, PdfEngineInterface $pdf_engine, $force_download = FALSE, $use_default_css = TRUE) {
    $pdf_engine->addPage($this->rendererFactory->create($entity)->getHtml($entity, $use_default_css, TRUE));

    // Allow other modules to alter the generated PDF object.
    $this->dispatcher->dispatch(PdfEvents::PRE_SEND, new PreSendPdfEvent($pdf_engine, $entity));

    // If we're forcing a download we need a filename otherwise it's just sent
    // straight to the browser.
    $filename = $force_download ? $this->generateFilename($entity) : NULL;

    return $pdf_engine->send($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipleEntitiesRenderedAsPdf(array $entities, PdfEngineInterface $pdf_engine, $force_download = FALSE, $use_default_css = TRUE) {
    $pdf_engine->addPage($this->rendererFactory->create($entities)->getHtmlMultiple($entities, $use_default_css, TRUE));

    // Allow other modules to alter the generated PDF object.
    $this->dispatcher->dispatch(PdfEvents::PRE_SEND_MULTIPLE, new PreSendPdfMultipleEvent($pdf_engine, $entities));

    // If we're forcing a download we need a filename otherwise it's just sent
    // straight to the browser.
    $filename = $force_download ? $this->generateMultiFilename($entities) : NULL;

    return $pdf_engine->send($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityRenderedAsHtml(EntityInterface $entity, $use_default_css = TRUE, $optimize_css = TRUE) {
    return $this->rendererFactory->create($entity)->getHtml($entity, $use_default_css, $optimize_css);
  }

  /**
   * Generate a filename from the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to generate the filename.
   * @param bool $with_extension
   *   Allow us to exclude the PDF file extension when generating the filename.
   *
   * @return string
   *   The cleaned filename from the entity label.
   */
  protected function generateFilename(EntityInterface $entity, $with_extension = TRUE) {
    $filename = preg_replace("/[^A-Za-z0-9 ]/", '', $entity->label());
    // If for some bizarre reason there isn't a valid character in the entity
    // title or the entity doesn't provide a label then we use the entity type.
    if (!$filename) {
      $filename = $entity->getEntityTypeId();
    }
    return $with_extension ? $filename . '.pdf' : $filename;
  }

  /**
   * @param array $entities
   *   An array of entities to derive the filename for.
   *
   * @return string
   *   The filename to use.
   * @param bool $with_extension
   *   Allow us to exclude the PDF file extension when generating the filename.
   *
   */
  protected function generateMultiFilename(array $entities, $with_extension = TRUE) {
    $filename = '';
    foreach ($entities as $entity) {
      $filename .= $this->generateFilename($entity, FALSE) . '-';
    }
    $filename = rtrim($filename, '-');

    return $with_extension ? $filename . '.pdf' : $filename;
  }

}
