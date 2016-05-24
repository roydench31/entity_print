<?php

namespace Drupal\entity_print\Renderer;

use Drupal\Core\Entity\EntityInterface;

interface RendererInterface {

  /**
   * Generate the HTML for our entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we're rendering.
   * @param bool $use_default_css
   *   TRUE if we should inject our default CSS otherwise FALSE.
   * @param bool $optimize_css
   *   TRUE if we should compress the CSS otherwise FALSE.
   *
   * @return string
   *   The generated HTML.
   *
   * @throws \Exception
   */
  public function getHtml(EntityInterface $entity, $use_default_css, $optimize_css);

  /**
   * Generate the HTML for our entity.
   *
   * @param array $entities
   *   An array of entities to generate the HTML for.
   * @param bool $use_default_css
   *   TRUE if we should inject our default CSS otherwise FALSE.
   * @param bool $optimize_css
   *   TRUE if we should compress the CSS otherwise FALSE.
   *
   * @return string
   *   The generated HTML.
   *
   * @throws \Exception
   */
  public function getHtmlMultiple($entities, $use_default_css, $optimize_css);

}
