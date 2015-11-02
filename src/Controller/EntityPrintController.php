<?php

/**
 * @file
 * Contains \Drupal\entity_print\Controller\EntityPrintController
 */

namespace Drupal\entity_print\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_print\PdfBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\entity_print\Plugin\EntityPrintPluginManager;

class EntityPrintController extends ControllerBase {

  /**
   * The plugin manager for our PDF engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManager
   */
  protected $pluginManager;

  /**
   * The PDF builder.
   *
   * @var \Drupal\entity_print\PdfBuilderInterface
   */
  protected $pdfBuilder;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityPrintPluginManager $plugin_manager, PdfBuilderInterface $pdf_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManager = $plugin_manager;
    $this->pdfBuilder = $pdf_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_print.pdf_engine'),
      $container->get('entity_print.pdf_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Output an entity as a PDF.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object on error otherwise the PDF is sent.
   */
  public function viewPdf($entity_type, $entity_id) {
    $response = new Response('Unable to find entity');

    // Render the entity as a PDF if we have a valid entity.
    if ($entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
      // Create the PDF engine plugin.
      $config = $this->config('entity_print.settings');
      $pdf_engine = $this->pluginManager
        ->createInstance($config->get('pdf_engine'), ['binary_location' => $config->get('binary_location')]);

      // Just set the content into the response. It will either be an error or
      // the PDF should just be sent to the browser.
      $response->setContent($this->pdfBuilder->getEntityRenderedAsPdf($entity, $pdf_engine, $config->get('force_download'), $config->get('default_css')));
    }
    return $response;
  }


  /**
   * A debug callback for styling up the PDF.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function viewPdfDebug($entity_type, $entity_id) {
    $response = new Response('Unable to find entity');

    if ($entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
      $use_default_css = $this->config('entity_print.settings')->get('default_css');
      $response->setContent($this->pdfBuilder->getEntityRenderedAsHtml($entity, $use_default_css, $this->config('system.performance')->get('css.preprocess')));
    }
    return $response;
  }

  /**
   * Validate that the current user has access.
   *
   * We need to validate that the user is allowed to access this entity also the
   * print version.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return bool
   *   TRUE if they have access otherwise FALSE.
   */
  public function checkAccess($entity_type, $entity_id) {
    $account = $this->currentUser();
    if (AccessResult::allowedIfHasPermission($account, 'entity print access')->isAllowed() && $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
      return $entity->access('view', $account, TRUE);
    }
    return AccessResult::forbidden();
  }

}
