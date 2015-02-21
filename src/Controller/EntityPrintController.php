<?php

/**
 * @file
 * Contains \Drupal\entity_print\Controller\EntityPrintController
 */

namespace Drupal\entity_print\Controller;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\entity_print\Plugin\EntityPrintPluginManager;
use Drupal\Core\Asset\AttachedAssets;

class EntityPrintController extends ControllerBase {

  /**
   * The plugin manager for our PDF engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManager
   */
  protected $pluginManager;

  /**
   * The info parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The asset resolver.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * The css asset renderer.
   *
   * @var \Drupal\Core\Asset\CssCollectionRenderer
   */
  protected $cssRenderer;

  /**
   * The renderer object.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityPrintPluginManager $plugin_manager, InfoParserInterface $info_parser, AssetResolverInterface $asset_resolver, AssetCollectionRendererInterface $css_renderer, RendererInterface $renderer) {
    $this->pluginManager = $plugin_manager;
    $this->infoParser = $info_parser;
    $this->assetResolver = $asset_resolver;
    $this->cssRenderer = $css_renderer;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_print.pdf_engine'),
      $container->get('info_parser'),
      $container->get('asset.resolver'),
      $container->get('asset.css.collection_renderer'),
      $container->get('renderer')
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
    if ($entity = entity_load($entity_type, $entity_id)) {
      $config = $this->config('entity_print.settings');
      $pdf_engine_id = $config->get('pdf_engine');

      /** @var \Drupal\entity_print\Plugin\PdfEngineInterface $pdf_engine */
      $pdf_engine = $this->pluginManager
        ->createInstance($pdf_engine_id, ['wkhtmltopdf_location' => $config->get('wkhtmltopdf_location')]);

      $html = $this->getHtml($entity);

      // Add a HTML file, a HTML string or a page from a URL
      $pdf_engine->addPage($html);

      // Allow other modules to alter the generated PDF object.
      $this->moduleHandler()->alter('entity_print_pdf', $pdf_engine, $entity);

      if (!$pdf_engine->send()) {
        return new Response($pdf_engine->getError());
      }
    }
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

    if ($entity = entity_load($entity_type, $entity_id)) {
      $response->setContent($this->getHtml($entity));
    }

    return $response;
  }

  /**
   * Generate the HTML for our entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity we're rendering.
   * @return string
   *   The generated HTML.
   *
   * @throws \Exception
   */
  protected function getHtml(ContentEntityInterface $entity) {
    $render_controller = $this->entityManager()->getViewBuilder($entity->getEntityTypeId());
    $render = [
      '#theme' => 'entity_print__' . $entity->getEntityTypeId() . '__' . $entity->id(),
      '#entity' => $entity,
      '#entity_array' => $render_controller->view($entity, 'pdf'),
      '#attached' => [],
    ];

    // Inject some generic CSS across all templates.
    $config = $this->config('entity_print.settings');
    if ($config->get('default_css')) {
      $render['#attached']['library'][] = 'entity_print/default';
    }

    // Allow other modules to add their own CSS.
    $this->moduleHandler()->alter('entity_print_css', $render, $entity);

    // Inject CSS from the theme info files and then render the CSS.
    $render = $this->addCss($render, $entity);
    $render['#entity_print_css'] = $this->renderCss($render);

    return $this->renderer->render($render);
  }

  /**
   * Inject the relevant css for the template.
   *
   * You can specify CSS files to be included per entity type and bundle in your
   * themes css file. This code uses your current theme which is likely to be the
   * front end theme.
   *
   * Examples:
   *
   * entity_print:
   *   all: 'yourtheme/all-pdfs',
   *   commerce_order:
   *     all: 'yourtheme/orders'
   *   node:
   *     article: 'yourtheme/article-pdf'
   *
   * @param array $render
   *   The renderable array.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity info from entity_get_info().
   *
   * @return array
   *   An array of stylesheets to be used for this template.
   */
  protected function addCss($render, ContentEntityInterface $entity) {

    $theme = \Drupal::service('theme_handler')->getDefault();
    $theme_path = drupal_get_path('theme', $theme);

    /** @var \Drupal\Core\Extension\InfoParser $parser */
    $theme_info = $this->infoParser->parse("$theme_path/$theme.info.yml");
    debug([$theme, $theme_info]);
    // Parse out the CSS from the theme info.
    if (isset($theme_info['entity_print'])) {

      // See if we have the special "all" key which is added to every PDF.
      if (isset($theme_info['entity_print']['all'])) {
        $render['#attached']['library'][] = $theme_info['entity_print']['all'];
        unset($theme_info['entity_print']['all']);
      }

      foreach ($theme_info['entity_print'] as $key => $value) {
        // If the entity type doesn't match just skip.
        if ($key !== $entity->getEntityTypeId()) {
          continue;
        }

        // Parse our css files per entity type and bundle.
        foreach ($value as $css_bundle => $css) {
          // If it's magic key "all" add it otherwise check the bundle.
          if ($css_bundle === 'all' || $entity->bundle() === $css_bundle) {
            $render['#attached']['library'][] = $css;
          }
        }
      }
    }

    return $render;
  }

  /**
   * A helper to render the CSS into our render array ready for printing.
   *
   * @param array $render
   *   The renderable array.
   *
   * @return string
   *   The rendered CSS.
   */
  protected function renderCss($render) {
    $optimize_css = !defined('MAINTENANCE_MODE') && $this->config('system.performance')->get('css.preprocess');
    $css_assets = $this->assetResolver->getCssAssets(AttachedAssets::createFromRenderArray($render), $optimize_css);
    $css = $this->cssRenderer->render($css_assets);

    return $this->renderer->render($css);
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
    if (AccessResult::allowedIfHasPermission($account, 'entity print access')->isAllowed() && $entity = entity_load($entity_type, $entity_id)) {
      return $entity->access('view', $account, TRUE);
    }
    return AccessResult::forbidden();
  }

}
