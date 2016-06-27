<?php

namespace Drupal\entity_print\Plugin\EntityPrint\ExportType;

use Drupal\Core\Plugin\PluginBase;

class DefaultExportType extends PluginBase {

  /**
   * The export type label.
   *
   * @return string
   *   The label string.
   */
  public function label() {
    return $this->getPluginDefinition()['label'];
  }

}
