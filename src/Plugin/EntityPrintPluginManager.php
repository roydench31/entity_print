<?php

/**
 * @file
 * Contains \Drupal\entity_print\Plugin\EntityPrintPluginManager
 */

namespace Drupal\entity_print\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\entity_print\PdfEngineException;

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
    parent::__construct('Plugin/EntityPrint/PdfEngine', $namespaces, $module_handler, 'Drupal\entity_print\Plugin\PdfEngineInterface', 'Drupal\entity_print\Annotation\PdfEngine');
    $this->alterInfo('entity_print_pdf_engine');
    $this->setCacheBackend($cache_backend, 'entity_print_pdf_engines');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $configuration = array_merge($this->getPdfEngineSettings($plugin_id), $configuration);

    /** @var \Drupal\entity_print\Plugin\PdfEngineInterface $class */
    $definition = $this->getDefinition($plugin_id);
    $class = $definition['class'];

    // Throw an exception if someone tries to use a plugin that doesn't have all
    // of its dependencies met.
    if (!$class::dependenciesAvailable()) {
      throw new PdfEngineException(sprintf('Missing dependencies. %s', $class::getInstallationInstructions()));
    }

    return parent::createInstance($plugin_id, $configuration);
  }

  /**
   * Gets the entity config settings for this plugin.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return array
   *   An array of PDF engine settings for this plugin.
   */
  protected function getPdfEngineSettings($plugin_id) {
    /** @var \Drupal\entity_print\Entity\PdfEngineInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('pdf_engine');
    if (!$entity = $storage->load($plugin_id)) {
      $entity = $storage->create(['id' => $plugin_id]);
    }
    return $entity->getSettings();
  }

}
