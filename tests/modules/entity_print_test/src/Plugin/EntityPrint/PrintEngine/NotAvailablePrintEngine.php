<?php

/**
 * @file
 * Contains \Drupal\entity_print_test\Plugin\EntityPrint\PrintEngine\NotAvailablePrintEngine
 */

namespace Drupal\entity_print_test\Plugin\EntityPrint\PrintEngine;

use Drupal\entity_print\Plugin\PrintEngineBase;

class NotAvailablePrintEngine extends PrintEngineBase {

  /**
   * {@inheritdoc}
   */
  public function send($filename = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function getError() {}

  /**
   * {@inheritdoc}
   */
  public function addPage($content) {}

  /**
   * {@inheritdoc}
   */
  public static function dependenciesAvailable() {
    return FALSE;
  }

}
