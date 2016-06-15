<?php

namespace Drupal\entity_print\Renderer;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_print\Event\PdfCssAlterEvent;
use Drupal\entity_print\Event\PdfEvents;
use Drupal\entity_print\Event\PdfHtmlAlterEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentEntityRenderer extends RendererBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(ThemeHandlerInterface $theme_handler, InfoParserInterface $info_parser, AssetResolverInterface $asset_resolver, AssetCollectionRendererInterface $css_renderer, RendererInterface $renderer, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($theme_handler, $info_parser, $asset_resolver, $css_renderer, $renderer, $event_dispatcher);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getHtml(EntityInterface $entity, $use_default_css, $optimize_css) {
    $render_controller = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $render = [
      '#theme' => 'entity_print__' . $entity->getEntityTypeId() . '__' . $entity->bundle() . '__' . $this->getViewMode($entity) . '__' . $entity->id(),
      '#entity' => $entity,
      '#entity_array' => $render_controller->view($entity, $this->getViewMode($entity)),
      '#attached' => [],
    ];

    return $this->generateHtml($render, [$entity], $use_default_css, $optimize_css);
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlMultiple($entities, $use_default_css, $optimize_css) {
    $first_entity = reset($entities);
    $render_controller = $this->entityTypeManager->getViewBuilder($first_entity->getEntityTypeId());

    // @TODO, maybe we should implement a different theme function?
    $render = [
      '#theme' => 'entity_print__' . $first_entity->getEntityTypeId(),
      '#entity' => $entities,
      '#entity_array' => $render_controller->viewMultiple($entities, $this->getViewMode($first_entity)),
      '#attached' => [],
    ];

    return $this->generateHtml($render, $entities, $use_default_css, $optimize_css);
  }

  /**
   * Generate the HTML for the PDF.
   *
   * @param array $render
   *   The renderable array for our Entity Print theme hook.
   * @param array $entities
   *   An array of entities that we're rendering.
   * @param bool $use_default_css
   *   TRUE if we're including the default CSS otherwise FALSE.
   * @param bool $optimize_css
   *   TRUE if we want to compress the CSS otherwise FALSE.
   *
   * @return string
   *   The HTML rendered string.
   */
  protected function generateHtml(array $render, array $entities, $use_default_css, $optimize_css) {
    // Inject some generic CSS across all templates.
    if ($use_default_css) {
      $render['#attached']['library'][] = 'entity_print/default';
    }

    foreach ($entities as $entity) {
      // Inject CSS from the theme info files and then render the CSS.
      $render = $this->addCss($render, $entity);
    }

    $this->dispatcher->dispatch(PdfEvents::CSS_ALTER, new PdfCssAlterEvent($render, $entities));
    $css_assets = $this->assetResolver->getCssAssets(AttachedAssets::createFromRenderArray($render), $optimize_css);
    $rendered_css = $this->cssRenderer->render($css_assets);
    $render['#entity_print_css'] = $this->renderer->render($rendered_css);

    $html = (string) $this->renderer->render($render);

    // Allow other modules to alter the generated HTML.
    $this->dispatcher->dispatch(PdfEvents::POST_RENDER, new PdfHtmlAlterEvent($html, $entities));

    return $html;
  }

  /**
   * Gets the view mode to use for this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity we're viewing.
   *
   * @return string
   *   The view mode machine name.
   */
  protected function getViewMode(EntityInterface $entity) {
    // We check to see if the PDF view display have been configured, if not
    // then we simply fall back to the full display.
    $view_mode = 'pdf';
    if (!$this->entityTypeManager->getStorage('entity_view_display')->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode)) {
      $view_mode = 'full';
    }
    return $view_mode;
  }

}
