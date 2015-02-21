<?php

/**
 * @file
 * Contains \Drupal\entity_print\Tests\EntityPrintTest
 */

namespace Drupal\entity_print\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Entity Print tests.
 *
 * @group Entity Print
 */
class EntityPrintTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity_print_test');

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
    // Create a user and login.
    $account = $this->drupalCreateUser(['entity print access'], $this->randomMachineName());
    $this->drupalLogin($account);

    // Create a content type and a dummy node.
    $this->drupalCreateContentType(array(
      'type' => 'page',
      'name' => 'Page',
    ));
    $this->node = $this->drupalCreateNode();

    // Install our custom theme.
    $theme = 'entity_print_test_theme';
    \Drupal::service('theme_handler')->install([$theme]);
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
  }

  /**
   * Test that CSS is parsed from our test theme correctly.
   */
  public function testEntityPrintThemeCss() {
    $this->drupalGet('entityprint/node/' . $this->node->id() . '/debug');
    $config = \Drupal::configFactory()->getEditable('entity_print.settings');

    // Test the global CSS is there.
    $this->assertRaw('entity-print.css');
    // Disable the global CSS and test it is not there.
    $config->set('default_css', FALSE)->save();
    $this->drupalGet($this->getUrl());
    $this->assertNoRaw('entity-print.css');

    // Assert that the css files have been parsed out of our test theme.
    $this->assertRaw('entityprint-all.css');
    $this->assertRaw('entityprint-page.css');
    $this->assertRaw('entityprint-node.css');

    // Test that CSS was added from hook_entity_print_css(). See the
    // entity_print_test module for the implementation.
    $this->assertRaw('entityprint-module.css');
  }

}
