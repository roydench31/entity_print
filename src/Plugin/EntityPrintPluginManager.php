<?php

namespace Drupal\entity_print\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\PrintEngineException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class EntityPrintPluginManager extends DefaultPluginManager {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EventDispatcherInterface $dispatcher) {
    parent::__construct('Plugin/EntityPrint/PrintEngine', $namespaces, $module_handler, 'Drupal\entity_print\Plugin\PrintEngineInterface', 'Drupal\entity_print\Annotation\PrintEngine');
    $this->alterInfo('entity_print_print_engine');
    $this->setCacheBackend($cache_backend, 'entity_print_print_engines');
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $configuration = array_merge($this->getPrintEngineSettings($plugin_id), $configuration);

    /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $class */
    $definition = $this->getDefinition($plugin_id);
    $class = $definition['class'];

    // Throw an exception if someone tries to use a plugin that doesn't have all
    // of its dependencies met.
    if (!$class::dependenciesAvailable()) {
      throw new PrintEngineException(sprintf('Missing dependencies. %s', $class::getInstallationInstructions()));
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
   *   An array of Print engine settings for this plugin.
   */
  protected function getPrintEngineSettings($plugin_id) {
    /** @var \Drupal\entity_print\Entity\PrintEngineInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('print_engine');
    if (!$entity = $storage->load($plugin_id)) {
      $entity = $storage->create(['id' => $plugin_id]);
    }
    $configuration = $entity->getSettings();
    $event = new GenericEvent(PrintEvents::CONFIGURATION_ALTER, ['configuration' => $configuration, 'config' => $entity]);
    $this->dispatcher->dispatch(PrintEvents::CONFIGURATION_ALTER, $event);
    $configuration = $event->getArgument('configuration');

    return $configuration;
  }

}
