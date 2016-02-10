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

    $account = $this->drupalCreateUser(['entity print access', 'access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'administer users', 'administer account settings', 'administer user display', 'bypass node access']);
    $this->drupalLogin($account);
  }

  /**
   * Test the view PDF extra field and the configurable text.
   */
  public function testViewPdfLink() {

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

    // Visit out page node and ensure the link is available.
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
