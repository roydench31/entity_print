<?php

/**
 * @file
 * Contains \Drupal\entity_print\Unit\EntityPrintTest
 */

namespace Drupal\entity_print\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_print\EntityPrintPdfBuilder
 * @group entity_print
 */
class EntityPrintTest extends UnitTestCase {

  /**
   * Test the rendered entity as PDF.
   *
   * @covers ::getEntityRenderedAsPdf
   * @dataProvider entityAsPdfDataProvider
   */
  public function testEntityRenderedAsPdf($force_download, $use_css, $send_result, $expected) {
    $pdf_engine = $this->getMockPdfEngine($force_download, $send_result);
    $entity = $this->getMockEntity($force_download ? 'myfile' : '');
    $pdf_builder = $this->getMockPdfBuilder($entity, $use_css, $force_download);

    // Run the tests and assert the results.
    $result = $pdf_builder->getEntityRenderedAsPdf($entity, $pdf_engine, $force_download, $use_css);
    $this->assertEquals($expected, $result);
  }

  /**
   * Entity print data provider.
   *
   * @return array
   *   An array of data combinations.
   */
  public function entityAsPdfDataProvider() {
    return [
      // $force_download, $use_default_css, $send_result, $expected.
      [TRUE, TRUE, TRUE, TRUE],
      [FALSE, TRUE, TRUE, TRUE],
      [TRUE, FALSE, TRUE, TRUE],
      [TRUE, TRUE, FALSE, 'Sending PDF failed'],
    ];
  }

  /**
   * Test safe file generation.
   *
   * @covers ::generateFilename
   * @dataProvider generateFilenameDataProvider
   */
  public function testGenerateFilename($entity_label, $expected_filename) {
    $force_download = $use_css = TRUE;
    $entity = $this->getMockEntity($entity_label);
    $pdf_engine = $this->getMockPdfEngine($force_download, TRUE, $expected_filename);

    $pdf_builder = $this->getMockPdfBuilder($entity, TRUE);
    $pdf_builder->getEntityRenderedAsPdf($entity, $pdf_engine, $force_download, $use_css);
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
   * @return \Drupal\entity_print\EntityPrintPdfBuilder
   *   The entity pdf builder mock.
   */
  protected function getMockPdfBuilder($entity, $use_css) {
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler
      ->expects($this->once())
      ->method('alter');

    $pdf_builder = $this->getMockBuilder('Drupal\entity_print\EntityPrintPdfBuilder')
      ->disableOriginalConstructor()
      ->setMethods(['getHtml'])
      ->getMock();
    $pdf_builder
      ->expects($this->once())
      ->method('getHtml')
      ->with($entity, $use_css, TRUE)
      ->willReturn('<custom> html');

    // Some reflection magic to replace the module handler.
    $reflection = new \ReflectionClass($pdf_builder);
    $property = $reflection->getProperty('moduleHandler');
    $property->setAccessible(true);
    $property->setValue($pdf_builder, $module_handler);

    return $pdf_builder;
  }

  /**
   * Get a mock pdf engine.
   *
   * @param bool $force_download
   *   Whether to force the pdf download.
   * @param bool $send_result
   *   The result from send.
   * @param string $filename
   *   The PDF filename.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mock pdf engine,
   */
  protected function getMockPdfEngine($force_download, $send_result, $filename = 'myfile.pdf') {
    $pdf_engine = $this->getMock('Drupal\entity_print\Plugin\PdfEngineInterface');
    $pdf_engine
      ->expects($this->once())
      ->method('addPage');
    $pdf_engine
      ->expects($this->once())
      ->method('send')
      ->with($force_download ? $filename : NULL)
      ->willReturn($send_result);
    if (!$send_result) {
      $pdf_engine
        ->expects($this->once())
        ->method('getError')
        ->willReturn('Sending PDF failed');
    }
    return $pdf_engine;
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
    $entity = $this->getMock('Drupal\Core\Entity\ContentEntityInterface');
    if ($entity_label) {
      $entity
        ->expects($this->once())
        ->method('label')
        ->willReturn($entity_label);
    }
    return $entity;
  }

}
