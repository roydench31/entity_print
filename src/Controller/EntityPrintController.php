<?php

/**
 * @file
 */

namespace Drupal\entity_print\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\entity_print\PdfBuilderInterface;
use Drupal\entity_print\PdfEngineException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\entity_print\Plugin\EntityPrintPluginManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    // Create the PDF engine plugin.
    $config = $this->config('entity_print.settings');
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);

    try {
      $pdf_engine = $this->pluginManager->createInstance($config->get('pdf_engine'));
    }
    catch (PdfEngineException $e) {
      // Build a safe markup string using Xss::filter() so that the instructions
      // for installing dependencies can contain quotes.
      drupal_set_message(new FormattableMarkup('Error generating PDF: ' . Xss::filter($e->getMessage()), []), 'error');

      return new RedirectResponse($entity->toUrl()->toString());
    }

    return (new StreamedResponse(function() use ($entity, $pdf_engine, $config) {
      // The PDF is sent straight to the browser.
      $this->pdfBuilder->getEntityRenderedAsPdf($entity, $pdf_engine, $config->get('force_download'), $config->get('default_css'));
    }))->send();
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
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    try {
      $use_default_css = $this->config('entity_print.settings')->get('default_css');
      return new Response($this->pdfBuilder->getEntityRenderedAsHtml($entity, $use_default_css, $this->config('system.performance')->get('css.preprocess')));
    }
    catch (PdfEngineException $e) {
      drupal_set_message(new FormattableMarkup('Error generating PDF: ' . Xss::filter($e->getMessage()), []), 'error');
      return new RedirectResponse($entity->toUrl()->toString());
    }
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
    if (empty($entity_id)) {
      return AccessResult::forbidden();
    }

    $account = $this->currentUser();

    // Invalid storage type.
    if (!$this->entityTypeManager->hasHandler($entity_type, 'storage')) {
      return AccessResult::forbidden();
    }

    // Unable to find the entity requested.
    if (!$entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
      return AccessResult::forbidden();
    }

    // Check if the user has the permission "bypass entity print access".
    $access_result = AccessResult::allowedIfHasPermission($account, 'bypass entity print access');
    if ($access_result->isAllowed()) {
      return $access_result->andIf($entity->access('view', $account, TRUE));
    }

    // Check if the user is allowed to view all bundles of the entity type.
    $access_result = AccessResult::allowedIfHasPermission($account, 'entity print access type ' . $entity_type);
    if ($access_result->isAllowed()) {
      return $access_result->andIf($entity->access('view', $account, TRUE));
    }

    // Check if the user is allowed to view that bundle type.
    $access_result = AccessResult::allowedIfHasPermission($account, 'entity print access bundle ' . $entity->bundle());
    if ($access_result->isAllowed()) {
      return $access_result->andIf($entity->access('view', $account, TRUE));
    }

    return AccessResult::forbidden();
  }

}
