<?php

namespace Drupal\entity_print\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

interface EntityPrintPluginManagerInterface extends PluginManagerInterface {

  /**
   * Checks if a plugin is enabled based on its dependencies.
   *
   * @param string $plugin_id
   *   The plugin id to check
   *
   * @return bool
   *   TRUE if the plugin is disabled otherwise FALSE.
   */
  public function isPrintEngineEnabled($plugin_id);

  /**
   * Gets all disabled print engine definitions.
   *
   * @param string $filter_export_type
   *   (optional) The export type you want to filter by.
   *
   * @return array
   *   An array of disabled print engine definitions keyed by export type.
   */
  public function getDisabledDefinitions($filter_export_type = NULL);

}
