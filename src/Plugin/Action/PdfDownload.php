<?php

namespace Drupal\entity_print\Plugin\Action;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_print\PrintBuilderInterface;
use Drupal\entity_print\PrintEngineException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads the PDF for an entity.
 *
 * @Action(
 *   id = "entity_print_pdf_download_action",
 *   label = @Translation("Download PDF"),
 *   type = "node"
 * )
 *
 * @TODO, support multiple entity types once core is fixed.
 * @see https://www.drupal.org/node/2011038
 */
class PdfDownload extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The Print builder service.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * The Entity Print plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Our custom configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $entityPrintConfig;

  /**
   * The Print engine implementation.
   *
   * @var \Drupal\entity_print\Plugin\PrintEngineInterface
   */
  protected $printEngine;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, PrintBuilderInterface $print_builder, PluginManagerInterface $plugin_manager, ImmutableConfig $entity_print_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accessManager = $access_manager;
    $this->printBuilder = $print_builder;
    $this->pluginManager = $plugin_manager;
    $this->entityPrintConfig = $entity_print_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('entity_print.print_manager'),
      $container->get('plugin.manager.entity_print.print_engine'),
      $container->get('config.factory')->get('entity_print.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $route_params = [
      'export_type' => 'pdf',
      'entity_id' => $object->id(),
      'entity_type' => $object->getEntityTypeId(),
    ];
    return $this->accessManager->checkNamedRoute('entity_print.view', $route_params, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->sendResponse((function() use ($entity) {
      $this->printBuilder->printSingle($entity, $this->getPrintEngine(), TRUE);
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->sendResponse((function() use ($entities) {
      $this->printBuilder->printMultiple($entities, $this->getPrintEngine(), TRUE);
    }));
  }

  /**
   * Sends the response using a stream and catches any errors.
   *
   * @param callable $callback
   *   The callable responding for rendering the content.
   */
  protected function sendResponse(callable $callback) {
    try {
      (new StreamedResponse($callback))->send();
    }
    catch (PrintEngineException $e) {
      drupal_set_message(new FormattableMarkup(Xss::filter($e->getMessage()), []), 'error');
    }
  }

  /**
   * Gets the Print engine implementation.
   *
   * @return \Drupal\entity_print\Plugin\PrintEngineInterface
   *   The Print Engine implementation.
   */
  protected function getPrintEngine() {
    if (!isset($this->printEngine)) {
      $this->printEngine = $this->pluginManager->createInstance($this->entityPrintConfig->get('print_engines.pdf_engine'));
    }
    return $this->printEngine;
  }

}
