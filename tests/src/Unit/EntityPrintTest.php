<?php

namespace Drupal\Tests\entity_print\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_print\EntityPrintPdfBuilder
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
    $pdf_builder = $this->getMockPdfBuilder();

    $reflection = new \ReflectionClass($pdf_builder);
    $method = $reflection->getMethod('generateFilename');
    $method->setAccessible(true);

    $this->assertEquals($expected_filename, $method->invoke($pdf_builder, $entity));
  }

  /**
   * Test multiple file generation.
   *
   * @covers ::generateMultiFilename
   * @dataProvider generateMultipleFilenameDataProvider
   */
  public function testGenerateMultipleFilename($entity_labels, $expected_filename) {
    $entities = [];

    foreach ($entity_labels as $entity_label) {
      $entities[] = $this->getMockEntity($entity_label);
    }

    $pdf_builder = $this->getMockPdfBuilder();

    $reflection = new \ReflectionClass($pdf_builder);
    $method = $reflection->getMethod('generateMultiFilename');
    $method->setAccessible(true);

    $this->assertEquals($expected_filename, $method->invoke($pdf_builder, $entities));
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
   * Data provider for multiple filename generation.
   *
   * @return array
   *   An array of data rows for testing filename generation.
   */
  public function generateMultipleFilenameDataProvider() {
    return [
      [['Node Title1', 'Node Title2', 'Node Title3'], 'Node Title1-Node Title2-Node Title3.pdf'],
      [['Title1 -=with special chars&*#', 'Title2 -=with special chars&*#', 'Title3 -=with special chars&*#'], 'Title1 with special chars-Title2 with special chars-Title3 with special chars.pdf'],
    ];
  }

  /**
   * Get a mock pdf builder.
   *
   * @return \Drupal\entity_print\EntityPrintPdfBuilder
   *   The entity pdf builder mock.
   */
  protected function getMockPdfBuilder() {
    $pdf_builder = $this->getMockBuilder('Drupal\entity_print\EntityPrintPdfBuilder')
      ->disableOriginalConstructor()
      ->setMethods([])
      ->getMock();

    return $pdf_builder;
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
