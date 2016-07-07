<?php

namespace Drupal\entity_print\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Session\AccountInterface;

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
  public static $modules = ['node', 'entity_print_test'];

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

    // We revoke the access content permission because we use that to test our
    // permissions around entity view.
    user_role_revoke_permissions(AccountInterface::ANONYMOUS_ROLE, ['access content']);
    user_role_revoke_permissions(AccountInterface::AUTHENTICATED_ROLE, ['access content']);

    // Create a content type and a dummy node.
    $this->createContentType(['type' => 'page', 'name' => 'Page']);
    $this->node = $this->createNode();
  }

  /**
   * Test the access works for viewing the PDF's.
   */
  public function testEntityPrintAccess() {
    // User with bypass entity print access but not content access.
    $account = $this->drupalCreateUser(array('bypass entity print access'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'User with only the bypass entity print access permission cannot view PDF.');

    // User with access content but not entity print access.
    $account = $this->drupalCreateUser(['access content'], $this->randomMachineName());
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'User with access content but no entity print permission cannot view PDF.');

    // User with both bypass entity print access and entity view.
    $account = $this->drupalCreateUser(array('bypass entity print access', 'access content'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(200, 'User with both permissions can view the PDF.');

    // User with entity type access permission and entity view.
    $account = $this->drupalCreateUser(array('entity print access type node', 'access content'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(200, 'User with entity print type and access content permission is allowed to see the content.');

    // User with different entity type access permission and entity view.
    $account = $this->drupalCreateUser(array('entity print access type user', 'access content'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'User with different entity print type and access content permission is not allowed to see the content.');

    // User with entity bundle access permission and entity view.
    $account = $this->drupalCreateUser(array('entity print access bundle page', 'access content'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(200, 'User with entity print bundle and access content permission is allowed to see the content.');

    // User with different bundle permission and entity view.
    $account = $this->drupalCreateUser(array('entity print access bundle user', 'access content'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'User with different entity print bundle and access content permission is not allowed to see the content.');

    // User with print bundle user permissions and entity view.
    $account = $this->drupalCreateUser(array('entity print access bundle user', 'access content'));
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/user/' . $account->id() . '/debug');
    $this->assertResponse(200, 'User with entity print user bundle permission and access content permission is allowed to see the content.');

    // User with neither permissions.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('entityprint/pdf/node/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'User with neither permission cannot view the PDF.');

    // Invalid entity type causes access denied.
    $this->drupalGet('entityprint/pdf/invalid/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'Invalid entity type triggers access denied.');

    // Invalid entity id also triggers access denied.
    $this->drupalGet('entityprint/pdf/node/invalid-entity-id/debug');
    $this->assertResponse(403, 'Invalid entity id triggers access denied.');

    // Invalid export type also triggers access denied.
    $this->drupalGet('entityprint/invalid/node/' . $this->node->id() . '/debug');
    $this->assertResponse(403, 'Invalid entity id triggers access denied.');
  }

}
