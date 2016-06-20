<?php

namespace Drupal\Tests\entity_print\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_print\PrintBuilder
 * @group entity_print
 */
class EntityPrintTest extends UnitTestCase {

  /**
   * Test safe file generation.
   *
   * @covers ::generateFilename
   * @dataProvider generateFilenameDataProvider
   */
  public function testGenerateFilename($entity_label, $expected_filename) {
    $entity = $this->getMockEntity($entity_label);
    $print_builder = $this->getMockPrintBuilder();

    $reflection = new \ReflectionClass($print_builder);
    $method = $reflection->getMethod('generateFilename');
    $method->setAccessible(TRUE);

    $this->assertEquals($expected_filename, $method->invoke($print_builder, $entity));
  }

  /**
   * Get the data for testing filename generation.
   *
   * @return array
   *   An array of data rows for testing filename generation.
   */
  public function generateFilenameDataProvider() {
    return [
      // $node_title, $expected_filename.
      ['Random Node Title', 'Random Node Title.pdf'],
      ['Title -=with special chars&*#', 'Title with special chars.pdf'],
      ['Title 5 with Nums 2', 'Title 5 with Nums 2.pdf'],
    ];
  }

  /**
   * Get a mock pdf builder.
   *
   * @return \Drupal\entity_print\PrintBuilder
   *   The entity pdf builder mock.
   */
  protected function getMockPrintBuilder() {
    $print_builder = $this->getMockBuilder('Drupal\entity_print\PrintBuilder')
      ->disableOriginalConstructor()
      ->setMethods([])
      ->getMock();

    return $print_builder;
  }

  /**
   * Get a mock entity for testing.
   *
   * @param string $entity_label
   *   (optional) The label title for the entity.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The content entity mock.
   */
  protected function getMockEntity($entity_label = '') {
    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    if ($entity_label) {
      $entity
        ->expects($this->any())
        ->method('label')
        ->willReturn($entity_label);
    }
    return $entity;
  }

}
