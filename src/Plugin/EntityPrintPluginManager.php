<?php

/**
 * @file
 * Contains \Drupal\entity_print\Plugin\EntityPrintPluginManager
 */

namespace Drupal\entity_print\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class EntityPrintPluginManager extends DefaultPluginManager {

  /**
   * Constructs a EntityPrintPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityPrint/PdfEngine', $namespaces, $module_handler, 'Drupal\entity_print\Plugin\PdfEngineInterface', 'Drupal\Component\Annotation\PluginID');
    $this->alterInfo('entity_print_pdf_engine');
    $this->setCacheBackend($cache_backend, 'entity_print_pdf_engines');
  }

}
