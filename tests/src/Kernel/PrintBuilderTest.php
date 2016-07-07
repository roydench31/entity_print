<?php

namespace Drupal\Tests\entity_print\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\NodeCreationTrait;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\entity_print\PrintBuilder
 * @group entity_print
 */
class PrintBuilderTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'node', 'filter', 'entity_print', 'entity_print_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'filter']);
    $this->container->get('theme_handler')->install(['stark']);
    $node_type = NodeType::create(['name' => 'Page', 'type' => 'page']);
    $node_type->setDisplaySubmitted(FALSE);
    $node_type->save();
  }

  /**
   * @covers ::deliverPrintable
   * @dataProvider outputtedFileDataProvider
   */
  public function testOutputtedFilename($print_engine_id, $file_name) {
    $print_engine = $this->container->get('plugin.manager.entity_print.print_engine')->createInstance($print_engine_id);
    $node = $this->createNode(['title' => 'myfile']);

    ob_start();
    $this->container->get('entity_print.print_manager')->deliverPrintable([$node], $print_engine, TRUE);
    $contents = ob_get_contents();
    ob_end_clean();
    $this->assertTrue(strpos($contents, $file_name) !== FALSE, "The $file_name file was found in $contents");
  }

  /**
   * Provides a data provider for testOutputtedFilename().
   */
  public function outputtedFileDataProvider() {
    return [
      'PDF file' => ['testprintengine', 'myfile.pdf'],
      'Word doc file' => ['test_word_print_engine', 'myfile.docx'],
    ];
  }

}
