<?php

/**
 * @file
 * Contains \Drupal\entity_print\Plugin\PdfEngineInterface
 */

namespace Drupal\entity_print\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface PdfEngineInterface extends PluginInspectionInterface {

  /**
   * Add a string of HTML to a new page.
   *
   * @param string $content
   *   The string of HTML to add to a new page.
   *
   * @return $this
   */
  public function addPage($content);

  /**
   * Send the PDF contents to the browser.
   *
   * @param $filename
   *   (optional) The filename if we want to force the browser to download.
   *
   * @return bool
   *   TRUE if the PDF contents were sent otherwise FALSE.
   */
  public function send($filename = NULL);

  /**
   * Get any errors during PDF creation or sending.
   *
   * @return string
   *   The error message.
   */
  public function getError();

  /**
   * Get the PDF implementation.
   *
   * You should not use this unless you know which engine you're expecting.
   *
   * @return mixed
   *   The raw PDF implementation.
   */
  public function getInstance();

}
