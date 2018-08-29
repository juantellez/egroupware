<?php

/**
 * Tests for ACL command
 *
 * @link http://www.egroupware.org
 * @author Nathan Gray
 * @copyright (c) 2018  Nathan Gray
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

// test base providing common stuff
require_once __DIR__.'/CommandBase.php';

use EGroupware\Api;

class UserCommandTest extends CommandBase {

	// User for testing
	protected $account_id;

	// Define account details once, then modify as needed for tests
	protected $account = array(
		'account_lid' => 'user_test',
		'account_firstname' => 'UserCommand',
		'account_lastname' => 'Test'
	);

	public function tearDown()
	{
		if($this->account_id)
		{
			$GLOBALS['egw']->accounts->delete($this->account_id);
		}
		parent::tearDown();
	}

	/**
	 * Test that adding a user works when we give it what it needs
	 */
	public function testAddUser()
	{
		// Set up
		$pre_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$log_count = $this->get_log_count();

		// Execute
		$command = new admin_cmd_edit_user(false, $this->account);
		$command->comment = 'Needed for unit test ' . $this->getName();
		$command->run();
		$this->account_id = $command->account;

		// Check
		$post_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));

		$this->assertNotEmpty($this->account_id, 'Did not create test user account');
		$this->assertEquals(count($pre_search) + 1, count($post_search), 'Should have one more account than before');
		$this->assertArrayHasKey($this->account_id, $post_search);
		$this->assertGreaterThan($log_count, $this->get_log_count(), "Command ($command) did not log");
	}

	/**
	 * Try to add a new user with the same login as current user.  It should
	 * throw an exception
	 */
	public function testUserAlreadyExists()
	{
		// Set up
		$pre_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->expectException(Api\Exception\WrongUserinput::class);

		// Execute
		$this->account['account_lid'] = $GLOBALS['egw_info']['user']['account_lid'];
		$command = new admin_cmd_edit_user(false, $this->account);
		$command->comment = 'Needed for unit test ' . $this->getName();
		$command->run();
		$this->account_id = $command->account;

		// Check
		$post_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->assertEquals(count($pre_search), count($post_search), 'Should have same number of accounts as before');
	}

	/**
	 * Try to add a new user without specifying the login.  It should throw an
	 * exception
	 */
	public function testLoginMissing()
	{
		// Set up
		$pre_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->expectException(Api\Exception\WrongUserinput::class);
		$account = $this->account;
		unset($account['account_lid']);

		// Execute
		$command = new admin_cmd_edit_user(false, $account);
		$command->comment = 'Needed for unit test ' . $this->getName();
		$command->run();
		$this->account_id = $command->account;

		// Check
		$post_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->assertEquals(count($pre_search), count($post_search), 'Should have same number of accounts as before');
	}

	/**
	 * Try to add a new user without specifying the last name.  It should throw
	 * an exception
	 */
	public function testLastnameMissing()
	{
		// Set up
		$pre_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->expectException(Api\Exception\WrongUserinput::class);
		$account = $this->account;
		unset($account['account_lastname']);

		// Execute
		$command = new admin_cmd_edit_user(false, $account);
		$command->comment = 'Needed for unit test ' . $this->getName();
		$command->run();
		$this->account_id = $command->account;

		// Check
		$post_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->assertEquals(count($pre_search), count($post_search), 'Should have same number of accounts as before');
	}

	/**
	 * If password is provided, password2 must be provided or an exception is thrown
	 */
	public function testPasswordOnce()
	{
		// Set up
		$command = new admin_cmd_edit_user(false, $this->account);
		$command->comment = 'Setup for unit test ' . $this->getName();
		$command->run();
		$this->account_id = $command->account;

		$pre_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));

		$account = $this->account;
		$account['account_passwd'] = 'passw0rd';

		$this->expectException(Api\Exception\WrongUserinput::class);

		// Execute
		$command = new admin_cmd_edit_user(false, $account);
		$command->comment = 'Needed for unit test ' . $this->getName();
		$command->run();

		// Check
		$post_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->assertEquals(count($pre_search), count($post_search), 'Should have same number of accounts as before');
	}

	/**
	 * If password is provided, password2 must be provided and the same or an
	 * an exception is thrown
	 */
	public function testPasswordMismatch()
	{
		// Set up
		$command = new admin_cmd_edit_user(false, $this->account);
		$command->comment = 'Setup for unit test ' . $this->getName();
		$command->run();
		$this->account_id = $command->account;

		$pre_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));

		$account = $this->account;
		$account['account_passwd'] = 'passw0rd';
		$account['account_passwd_2'] = 'pAssw0rd';

		$this->expectException(Api\Exception\WrongUserinput::class);

		// Execute
		$command = new admin_cmd_edit_user(false, $account);
		$command->comment = 'Needed for unit test ' . $this->getName();
		$command->run();

		// Check
		$post_search = $GLOBALS['egw']->accounts->search(array('type' => 'both'));
		$this->assertEquals(count($pre_search), count($post_search), 'Should have same number of accounts as before');
	}
}