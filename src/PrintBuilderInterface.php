<?php

namespace Drupal\entity_print;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;

/**
 * Interface for the Print builder service.
 */
interface PrintBuilderInterface {

  /**
   * Render any content entity as a Print.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to render.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The plugin id of the Print engine to use.
   * @param bool $force_download
   *   (optional) TRUE to try and force the Print to be downloaded rather than opened.
   * @param bool $use_default_css
   *   (optional) TRUE if you want the default CSS included, otherwise FALSE.
   *
   * @return string
   *   FALSE or the Print content will be sent to the browser.
   */
  public function printSingle(EntityInterface $entity, PrintEngineInterface $print_engine, $force_download = FALSE, $use_default_css = TRUE);

  /**
   * Render any content entity as a Print.
   *
   * @param array $entities
   *   An array of content entities to render, 1 per page.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The plugin id of the Print engine to use.
   * @param bool $force_download
   *   (optional) TRUE to try and force the Print to be downloaded rather than opened.
   * @param bool $use_default_css
   *   (optional) TRUE if you want the default CSS included, otherwise FALSE.
   *
   * @return string
   *   FALSE or the Print content will be sent to the browser.
   */
  public function printMultiple(array $entities, PrintEngineInterface $print_engine, $force_download = FALSE, $use_default_css = TRUE);

  /**
   * Get a HTML version of the entity as used for the Print rendering.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to render.
   * @param bool $use_default_css
   *   TRUE if you want the default CSS included, otherwise FALSE.
   * @param bool $optimize_css
   *   TRUE if you the CSS should be compressed otherwise FALSE.
   *
   * @return string
   *   The rendered HTML for this entity, the same as what is used for the Print.
   */
  public function printHtml(EntityInterface $entity, $use_default_css = TRUE, $optimize_css = TRUE);
}
