<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentIntegrationTests extends PHPUnit_Framework_TestCase {

	/**
	 * Bootstrap Eloquent.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		Eloquent::setConnectionResolver(
			new DatabaseIntegrationTestConnectionResolver
		);

		Eloquent::setEventDispatcher(
			new Illuminate\Events\Dispatcher
		);
	}


	/**
	 * Tear down Eloquent.
	 */
	public static function tearDownAfterClass()
	{
		Eloquent::unsetEventDispatcher();
		Eloquent::unsetConnectionResolver();
	}


	/**
	 * Setup the database schema.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->schema()->create('users', function($table) {
			$table->increments('id');
			$table->string('email')->unique();
		});
	}


	/**
	 * Tear down the database schema.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		$this->schema()->drop('users');
	}

	/**
	 * Tests...
	 */
	public function testBasicModelRetrieval()
	{
		$this->connection()->table('users')->insert(['email' => 'taylorotwell@gmail.com']);
		$model = EloquentTestUser::where('email', 'taylorotwell@gmail.com')->first();
		$this->assertEquals('taylorotwell@gmail.com', $model->email);
	}

	/**
	 * Helpers...
	 */

	/**
	 * Get a database connection instance.
	 *
	 * @return Connection
	 */
	protected function connection()
	{
		return Eloquent::getConnectionResolver()->connection();
	}

	/**
	 * Get a schema builder instance.
	 *
	 * @return Schema\Builder
	 */
	protected function schema()
	{
		return $this->connection()->getSchemaBuilder();
	}

}

/**
 * Eloquent Models...
 */

class EloquentTestUser extends Eloquent {
	protected $table = 'users';
}

/**
 * Connection Resolver
 */

class DatabaseIntegrationTestConnectionResolver implements Illuminate\Database\ConnectionResolverInterface {

	protected $connection;

	public function connection($name = null)
	{
		if (isset($this->connection)) return $this->connection;
		return $this->connection = new Illuminate\Database\SQLiteConnection(new PDO('sqlite::memory:'));
	}
	public function getDefaultConnection()
	{
		return 'default';
	}
	public function setDefaultConnection($name)
	{
		//
	}
}
