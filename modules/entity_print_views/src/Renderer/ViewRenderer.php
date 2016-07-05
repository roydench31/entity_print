<?php

namespace Drupal\entity_print_views\Renderer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Renderer\RendererBase;

/**
 * Providers a renderer for Views.
 */
class ViewRenderer extends RendererBase {

  /**
   * {@inheritdoc}
   */
  public function getHtml(EntityInterface $view, $use_default_css, $optimize_css) {
    /** @var \Drupal\views\Entity\View $view */
    $executable = $view->getExecutable();
    $html = $executable->render();

    // We must remove ourselves from all areas otherwise it will cause an
    // infinite loop when rendering.
    foreach (['header', 'footer', 'empty'] as $area_type) {
      $handlers = &$executable->display_handler->getHandlers($area_type);
      unset($handlers['area_entity_print_views']);
    }

    $html['#pre_render'][] = [static::class, 'preRender'];
    $render = [
      '#theme' => 'entity_print__' . $view->getEntityTypeId() . '__' . $view->id(),
      '#entity' => $view,
      '#entity_array' => $html,
      '#attached' => [],
    ];

    return $this->generateHtml($render, [$view], $use_default_css, $optimize_css);
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlMultiple($entities, $use_default_css, $optimize_css) {
    $output = '';
    foreach ($entities as $entity) {
      $output .= $this->getHtml($entity, $use_default_css, $optimize_css);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(EntityInterface $view) {
    return $this->sanitizeFilename($view->getExecutable()->getTitle());
  }

  /**
   * Pre render callback for the view.
   */
  public static function preRender(array $element) {
    // Remove the exposed filters, we don't every want them on the PDF.
    $element['#exposed'] = [];
    return $element;
  }

}
