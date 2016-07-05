<?php

namespace Drupal\Tests\entity_print\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\entity_print\Plugin\EntityPrintPluginManager
 * @group entity_print
 */
class EntityPrintPluginManagerTest extends KernelTestBase {

  public static $modules = ['entity_print', 'entity_print_test'];

  /**
   * The plugin manager.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pluginManager = $this->container->get('plugin.manager.entity_print.print_engine');
  }

  /**
   * Test if an engine is enabled.
   *
   * @covers ::isPrintEngineEnabled
   * @dataProvider isPrintEngineEnabledDataProvider
   */
  public function testIsPrintEngineEnabled($plugin_id, $is_enabled) {
    $this->assertSame($this->pluginManager->isPrintEngineEnabled($plugin_id), $is_enabled);
  }

  /**
   * Data provider for isPrintEngineEnabled test.
   */
  public function isPrintEngineEnabledDataProvider() {
    return [
      'Non-existent plugin ID' => ['abc123', FALSE],
      'Empty plugin ID' => ['', FALSE],
      'Disabled plugin ID' => ['dompdf', FALSE],
      'Enabled plugin ID' => ['testprintengine', TRUE],
    ];
  }

  /**
   * @covers ::getDisabledDefinitions
   * @dataProvider getDisabledDefinitionsDataProvider
   */
  public function testGetDisabledDefinitions($filter, $expected_definitions) {
    $disabled_definitions = array_keys($this->pluginManager->getDisabledDefinitions($filter));
    sort($disabled_definitions);
    sort($expected_definitions);
    $this->assertSame($disabled_definitions, $expected_definitions);
  }

  /**
   * Data provider for getDisabledDefinitions test.
   */
  public function getDisabledDefinitionsDataProvider() {
    return [
      'Filter by pdf' => ['pdf', ['dompdf', 'phpwkhtmltopdf', 'not_available_print_engine']],
      'Filter by another type' => ['worddoc', []],
    ];
  }

}
