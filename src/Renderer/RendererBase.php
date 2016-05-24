<?php

namespace Drupal\entity_print\Renderer;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Render\RendererInterface as CoreRendererInterface;

/**
 * The RendererBase class.
 */
abstract class RendererBase implements RendererInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The info parser for yml files.
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
   * The renderer for renderable arrays.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  public function __construct(ThemeHandlerInterface $theme_handler, InfoParserInterface $info_parser, AssetResolverInterface $asset_resolver, AssetCollectionRendererInterface $css_renderer, CoreRendererInterface $renderer, EventDispatcherInterface $event_dispatcher) {
    $this->themeHandler = $theme_handler;
    $this->infoParser = $info_parser;
    $this->assetResolver = $asset_resolver;
    $this->cssRenderer = $css_renderer;
    $this->renderer = $renderer;
    $this->dispatcher = $event_dispatcher;
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity info from entity_get_info().
   *
   * @return array
   *   An array of stylesheets to be used for this template.
   */
  protected function addCss($render, EntityInterface $entity) {
    $theme = $this->themeHandler->getDefault();
    $theme_path = $this->getThemePath($theme);

    /** @var \Drupal\Core\Extension\InfoParser $parser */
    $theme_info = $this->infoParser->parse("$theme_path/$theme.info.yml");

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
   * Get the path to a theme.
   *
   * @param string $theme
   *   The name of the theme.
   *
   * @return string
   *   The Drupal path to the theme.
   */
  protected function getThemePath($theme) {
    return drupal_get_path('theme', $theme);
  }

}
