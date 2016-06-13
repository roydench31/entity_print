<?php

/**
 * @file
 * Contains \Drupal\entity_print\Form\SettingsForm.
 */

namespace Drupal\entity_print\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\entity_print\Plugin\EntityPrintPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures Entity Print settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Print engine plugin manager.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManager
   */
  protected $pluginManager;

  /**
   * The entity config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\entity_print\Plugin\EntityPrintPluginManager $plugin_manager
   *   The plugin manager object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The config storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityPrintPluginManager $plugin_manager, EntityStorageInterface $entity_storage) {
    parent::__construct($config_factory);
    $this->pluginManager = $plugin_manager;
    $this->storage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.entity_print.print_engine'),
      $container->get('entity_type.manager')->getStorage('print_engine')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'entity_print_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_print.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $disabled_engines = [];
    $print_engines = [];
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $class */
      $class = $definition['class'];
      $export_type = $definition['export_type'];

      if ($class::dependenciesAvailable()) {
        $print_engines[$export_type][$plugin_id] = $definition['label'];
      }
      else {
        $disabled_engines[$export_type][$plugin_id] = $definition['label'];

        // Show the user which Print engines are disabled, but only for
        // the page load not on AJAX requests.
        if (!$request->isXmlHttpRequest()) {
          drupal_set_message($this->t('@name is not available because it is not configured. @installation.', [
            '@name' => $definition['label'],
            '@installation' => $class::getInstallationInstructions(),
          ]), 'warning');
        }
      }
    }

    $config = $this->config('entity_print.settings');
    $form['entity_print'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Print Config'),
    ];
    $form['entity_print']['default_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Default CSS'),
      '#description' => $this->t('Provides some very basic font and padding styles.'),
      '#default_value' => $config->get('default_css'),
    ];
    $form['entity_print']['force_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force Download'),
      '#description' => $this->t('This option will attempt to force the browser to download the Print with a filename from the node title.'),
      '#default_value' => $config->get('force_download'),
    ];

    // @TODO, allow other print engines to be configured.
    $form['entity_print']['pdf_engine'] = [
      '#type' => 'select',
      '#title' => $this->t('PDF Engine'),
      '#description' => $this->t('Select the PDF engine to render the Print'),
      '#options' => !empty($print_engines['pdf']) ? $print_engines['pdf'] : [],
      '#default_value' => $config->get('print_engines.pdf_engine'),
      '#empty_option' => $this->t('- None -'),
      '#ajax' => [
        'callback' => '::ajaxPdfPluginFormCallback',
        'wrapper' => 'pdf-engine-config',
        'effect' => 'fade',
      ],
    ];
    $form['entity_print']['pdf_engine_config'] = [
      '#type' => 'container',
      '#id' => 'pdf-engine-config',
    ];

    // If we have a print_engine in the form_state then use that otherwise, fall
    // back to what was saved as this is a fresh form. Check explicitly for NULL
    // in case they selected the None option which is false'y.
    $pdf_plugin_id = !is_null($form_state->getValue('pdf_engine')) ? $form_state->getValue('pdf_engine') : $config->get('print_engines.pdf_engine');

    // If we have a plugin id and the plugin hasn't since been disabled then we
    // load the config for the plugin.
    // @TODO, support all print engine types.
    if ($pdf_plugin_id && !in_array($pdf_plugin_id, array_keys($disabled_engines['pdf']), TRUE)) {
      $form['entity_print']['pdf_engine_config'][$pdf_plugin_id] = $this->getPluginForm($pdf_plugin_id, $form_state);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax form callback.
   */
  public function ajaxPdfPluginFormCallback(&$form, FormStateInterface $form_state) {
    return $form['entity_print']['pdf_engine_config'];
  }

  /**
   * Gets a configuration form for the given plugin.
   *
   * @param string $plugin_id
   *   The plugin id for which we want the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The sub form structure for this plugin.
   */
  protected function getPluginForm($plugin_id, FormStateInterface $form_state) {
    $plugin = $this->pluginManager->createInstance($plugin_id);
    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('@engine Settings', ['@engine' => $plugin->getPluginDefinition()['label']]),
    ];
    return $form + $plugin->buildConfigurationForm([], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($plugin_id = $form_state->getValue('pdf_engine')) {
      // Load the config entity, submit the relevant plugin form and then save
      // it.
      $entity = $this->loadConfigEntity($plugin_id);
      /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $plugin */
      $plugin = $entity->getPrintEnginePluginCollection()->get($entity->id());
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($plugin_id = $form_state->getValue('pdf_engine')) {
      // Load the config entity, submit the relevant plugin form and then save
      // it.
      $entity = $this->loadConfigEntity($plugin_id);
      /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $plugin */
      $plugin = $entity->getPrintEnginePluginCollection()->get($entity->id());
      $plugin->submitConfigurationForm($form, $form_state);
      $entity->save();
    }

    // Save the global settings.
    $values = $form_state->getValues();
    $this->config('entity_print.settings')
      ->set('default_css', $values['default_css'])
      ->set('force_download', $values['force_download'])
      ->set('print_engines.pdf_engine', $values['pdf_engine'])
      ->save();
  }

  /**
   * Gets the config entity backing the specified plugin.
   *
   * @param string $plugin_id
   *   The Print engine plugin id.
   *
   * @return \Drupal\entity_print\Entity\PrintEngine
   *   The loaded config object backing the plugin.
   */
  protected function loadConfigEntity($plugin_id) {
    if (!$entity = $this->storage->load($plugin_id)) {
      $entity = $this->storage->create(['id' => $plugin_id]);
    }
    return $entity;
  }

}
