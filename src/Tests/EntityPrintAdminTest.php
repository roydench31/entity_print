<?php

/**
 * @file
 * Contains \Drupal\entity_print\Tests\EntityPrintAdminTest
 */

namespace Drupal\entity_print\Tests;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\simpletest\WebTestBase;

/**
 * Entity Print Admin tests.
 *
 * @group Entity Print
 */
class EntityPrintAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'entity_print_test', 'field', 'field_ui'];

  /**
   * The node object to test against.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a content type and a dummy node.
    $this->drupalCreateContentType(array(
      'type' => 'page',
      'name' => 'Page',
    ));
    $this->node = $this->drupalCreateNode();

    $account = $this->drupalCreateUser(['entity print access', 'administer entity print', 'access content', 'administer content types', 'administer node display']);
    $this->drupalLogin($account);
  }

  /**
   * Test the configuration form and expected settings.
   */
  public function testAdminSettings() {
    $this->drupalGet('/admin/config/content/entityprint');
    // The default implementation is Dompdf but that is not available in tests
    // make sure its settings form is not rendered.
    $this->assertNoText('Dompdf Settings');

    // Make sure we also get a warning telling us to install it.
    $this->assertText('Dompdf is not available because it is not configured. Please install using composer.');

    // Ensure saving the form without any PDF engine selected doesn't blow up.
    $this->drupalPostForm(NULL, [], 'Save configuration');

    // Assert the intial config values.
    $this->drupalPostAjaxForm(NULL, ['pdf_engine' => 'testpdfengine'], 'pdf_engine');
    $this->assertFieldByName('test_engine_setting', 'initial value');

    // Ensure the plugin gets the chance to validate the form.
    $this->drupalPostForm(NULL, [
      'pdf_engine' => 'testpdfengine',
      'test_engine_setting' => 'rejected',
    ], 'Save configuration');
    $this->assertText('Setting has an invalid value');

    $this->drupalPostForm(NULL, [
      'default_css' => 0,
      'force_download' => 0,
      'pdf_engine' => 'testpdfengine',
      'test_engine_setting' => 'testvalue',
    ], 'Save configuration');

    /** @var \Drupal\entity_print\Entity\PdfEngineInterface $config_entity */
    $config_entity = \Drupal::entityTypeManager()->getStorage('pdf_engine')->load('testpdfengine');

    // Assert the expected settings were stored.
    $this->assertEqual('testpdfengine', $config_entity->id());
    $this->assertEqual(['test_engine_setting' => 'testvalue'], $config_entity->getSettings());
    $this->assertEqual('entity_print_test', $config_entity->getDependencies()['module'][0]);

    // Assert that the testpdfengine is actually used.
    $this->drupalGet('/entityprint/node/1');
    $this->assertText('Using testpdfengine');
  }

  /**
   * Test the view PDF extra field and the configurable text.
   */
  public function testViewPdfLink() {
    // Run the module install actions as a workaround for the fact that the
    // page content type isn't created until setUp() here and therefore our PDF
    // view mode isn't added the first time. Note, this might causes issues if
    // we ever add to hook_install() actions that cannot run twice.
    module_load_install('entity_print');
    entity_print_install();

    // Visit the manage display.
    $this->drupalGet('admin/structure/types/manage/page/display');

    // Ensure the link doesn't appear by default.
    $this->drupalGet($this->node->toUrl());
    $this->assertNoText('View PDF');
    $this->assertNoLinkByHref('entityprint/node/1');

    // Save the display with new text.
    $random_text = $this->randomMachineName();
    $this->drupalPostForm('admin/structure/types/manage/page/display', [
      'fields[entity_print_view][empty_cell]' => $random_text,
      'fields[entity_print_view][type]' => 'visible',
    ], 'Save');

    // Assert that there is a PDF view mode available on nodes by default.
    $this->assertFieldByName('display_modes_custom[pdf]');

    // Visit our page node and ensure the link is available.
    $this->drupalGet($this->node->toUrl());
    $this->assertLink($random_text);
    $this->assertLinkByHref('/entityprint/node/1');

    // Load the EntityViewDisplay and ensure the settings are in the correct
    // place.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $display */
    $display = EntityViewDisplay::load('node.page.default');

    $this->assertIdentical($random_text, $display->getThirdPartySetting('entity_print', 'label'));
  }

}
