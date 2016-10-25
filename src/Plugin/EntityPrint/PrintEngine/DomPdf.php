<?php

namespace Drupal\entity_print\Plugin\EntityPrint\PrintEngine;

use Dompdf\Dompdf as DompdfLib;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_print\Plugin\ExportTypeInterface;
use Drupal\entity_print\PrintEngineException;
use Drupal\entity_print\Plugin\PrintEngineBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Dompdf\Adapter\CPDF;

/**
 * @PrintEngine(
 *   id = "dompdf",
 *   label = @Translation("Dompdf"),
 *   export_type = "pdf"
 * )
 *
 * To use this implementation you will need the DomPDF library, simply run
 *
 * @code
 *     composer require "dompdf/dompdf 0.7.0-beta3"
 * @endcode
 */
class DomPdf extends PrintEngineBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Dompdf\Dompdf
   */
  protected $dompdf;

  /**
   * Keep track of HTML pages as they're added.
   *
   * @var string
   */
  protected $html = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExportTypeInterface $export_type, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $export_type);
    $this->dompdf = new DompdfLib($this->configuration);
    $this->dompdf->setPaper($this->configuration['default_paper_size'], $this->configuration['orientation']);
    $this->dompdf
      ->setBaseHost($request->getHttpHost())
      ->setProtocol($request->getScheme() . '://');

    $this->setupHttpContext();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.entity_print.export_type')->createInstance($plugin_definition['export_type']),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getInstallationInstructions() {
    return t('Please install with: @command', ['@command' => 'composer require "dompdf/dompdf 0.7.0-beta3"']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enable_html5_parser' => TRUE,
      'enable_remote' => TRUE,
      'default_paper_size' => 'letter',
      'orientation' => 'portrait',
      'cafile' => '',
      'verify_peer' => TRUE,
      'verify_peer_name' => TRUE,
      'username' => '',
      'password' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $paper_sizes = array_combine(array_keys(CPDF::$PAPER_SIZES), array_map(function($value) {
      return ucfirst($value);
    }, array_keys(CPDF::$PAPER_SIZES)));
    $form['default_paper_size'] = [
      '#title' => $this->t('Paper Size'),
      '#type' => 'select',
      '#options' => $paper_sizes,
      '#default_value' => $this->configuration['default_paper_size'],
      '#description' => $this->t('The page size to print the PDF to.'),
    ];
    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Paper Orientation'),
      '#options' => [
        static::PORTRAIT => $this->t('Portrait'),
        static::LANDSCAPE => $this->t('Landscape'),
      ],
      '#description' => $this->t('The paper orientation one of Landscape or Portrait'),
      '#default_value' => $this->configuration['orientation'],
    ];
    $form['enable_html5_parser'] = [
      '#title' => $this->t('Enable HTML5 Parser'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable_html5_parser'],
      '#description' => $this->t('Note, this library doesn\'t work without this option enabled.'),
    ];
    $form['enable_remote'] = [
      '#title' => $this->t('Enable Remote URLs'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable_remote'],
      '#description' => $this->t('This settings must be enabled for CSS and Images to work unless you manipulate the source manually.'),
    ];
    $form['ssl_configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('SSL Configuration'),
      '#open' => !empty($this->configuration['cafile']) || empty($this->configuration['verify_peer']) || empty($this->configuration['verify_peer_name']),
    ];
    $form['ssl_configuration']['cafile'] = [
      '#title' => $this->t('CA File'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['cafile'],
      '#description' => $this->t('Path to the CA file. This may be needed for development boxes that use SSL'),
    ];
    $form['ssl_configuration']['verify_peer'] = [
      '#title' => $this->t('Verify Peer'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['verify_peer'],
      '#description' => $this->t('Verify an SSL Peer\'s certificate. For development only, do not disable this in production. See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html'),
    ];
    $form['ssl_configuration']['verify_peer_name'] = [
      '#title' => $this->t('Verify Peer Name'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['verify_peer_name'],
      '#description' => $this->t('Verify an SSL Peer\'s certificate. For development only, do not disable this in production. See https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html'),
    ];
    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('HTTP Authentication'),
      '#open' => !empty($this->configuration['username']) || !empty($this->configuration['password']),
    ];
    $form['credentials']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('If your website is behind HTTP Authentication you can set the username'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['credentials']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('If your website is behind HTTP Authentication you can set the password'),
      '#default_value' => $this->configuration['password'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($content) {
    // We must keep adding to previously added HTML as loadHtml() replaces the
    // entire document.
    $this->html .= (string) $content;
    $this->dompdf->loadHtml($this->html);
  }

  /**
   * {@inheritdoc}
   */
  public function send($filename = NULL) {
    $this->dompdf->render();

    // Dompdf doesn't have a return value for send so just check the error
    // global it provides.
    if ($errors = $this->getError()) {
      throw new PrintEngineException(sprintf('Failed to generate PDF: %s', $errors));
    }

    // The Dompdf library internally adds the .pdf extension so we remove it
    // from our filename here.
    $filename = preg_replace('/\.pdf$/i', '', $filename);

    // If the filename received here is NULL, force open in the browser
    // otherwise attempt to have it downloaded.
    $this->dompdf->stream($filename, ['Attachment' => (bool) $filename]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getError() {
    global $_dompdf_warnings;
    if (is_array($_dompdf_warnings)) {
      return implode(', ', $_dompdf_warnings);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function dependenciesAvailable() {
    return class_exists('Dompdf\Dompdf') && !drupal_valid_test_ua();
  }

  /**
   * Setup the HTTP Context used by Dompdf for requesting resources.
   */
  protected function setupHttpContext() {
    $context_options = [
      'ssl' => [
        'cafile' => $this->configuration['cafile'],
        'verify_peer' => $this->configuration['verify_peer'],
        'verify_peer_name' => $this->configuration['verify_peer_name'],
      ],
    ];

    // If we have authentication then add it to the request context.
    if (!empty($this->configuration['username'])) {
      $auth = base64_encode(sprintf('%s:%s', $this->configuration['username'], $this->configuration['password']));
      $context_options['http']['header'] = [
        'Authorization: Basic ' . $auth,
      ];
    }

    $http_context = stream_context_create($context_options);
    $this->dompdf->setHttpContext($http_context);
  }

}
