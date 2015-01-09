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
			$table->timestamps();
		});

		$this->schema()->create('friends', function($table) {
			$table->integer('user_id');
			$table->integer('friend_id');
		});

		$this->schema()->create('posts', function($table) {
			$table->increments('id');
			$table->integer('user_id');
			$table->string('name');
			$table->timestamps();
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
		$this->schema()->drop('friends');
		$this->schema()->drop('posts');
	}

	/**
	 * Tests...
	 */
	public function testBasicModelRetrieval()
	{
		EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
		$model = EloquentTestUser::where('email', 'taylorotwell@gmail.com')->first();
		$this->assertEquals('taylorotwell@gmail.com', $model->email);
	}


	public function testHasOnSelfReferencingBelongsToManyRelationship()
	{
		$user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
		$friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);

		$results = EloquentTestUser::has('friends')->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
	}


	public function testBasicHasManyEagerLoading()
	{
		$user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
		$user->posts()->create(['name' => 'First Post']);
		$user = EloquentTestUser::with('posts')->where('email', 'taylorotwell@gmail.com')->first();

		$this->assertEquals('First Post', $user->posts->first()->name);
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
	protected $guarded = [];
	public function friends() {
		return $this->belongsToMany('EloquentTestUser', 'friends', 'user_id', 'friend_id');
	}
	public function posts() {
		return $this->hasMany('EloquentTestPost', 'user_id');
	}
}

class EloquentTestPost extends Eloquent {
	protected $table = 'posts';
	protected $guarded = [];
	public function user() {
		return $this->belongsTo('EloquentTestUser', 'user_id');
	}
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
