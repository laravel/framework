<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseConcernsInteractsWithPivotTableTest extends TestCase
{
  protected function setUp(): void
  {
    $db = new Manager;

    $db->addConnection([
      'driver' => 'sqlite',
      'database' => ':memory:',
  ]);

    $db->bootEloquent();
    $db->setAsGlobal();

    $this->createSchema();
  }

  /**
   * Setup the database schema.
   *
   * @return void
   */
  public function createSchema()
  {
    $this->schema()->create('users', function ($table) {
      $table->increments('id');
      $table->string('name');
    });

    $this->schema()->create('roles', function ($table) {
      $table->increments('id');
      $table->string('name');
    });

    $this->schema()->create('role_user', function ($table) {
      $table->integer('role_id')->unsigned();
      $table->foreign('role_id')->references('id')->on('roles');
      $table->integer('user_id')->unsigned();
      $table->foreign('user_id')->references('id')->on('users');
      $table->string('office')->nullable();
    });
  }

  /**
   * Get a schema builder instance.
   *
   * @return \Illuminate\Database\Schema\Builder
   */
  protected function schema()
  {
    return $this->connection()->getSchemaBuilder();
  }


  protected function connection(): mixed
  {
    return Model::getConnectionResolver()->connection();
  }

  public function seed()
  {
    $user = PivotInteractionUser::create(['id' => 1, 'name' => 'Chibuike']);
    PivotInteractionRoles::insert([
      ['id' => 1, 'name' => 'Eater'],
      ['id' => 2, 'name' => 'Sleeper'],
      ['id' => 3, 'name' => 'Skier'],
    ]);

    $user->roles()->attach(PivotInteractionRoles::first());
  }

  public function testCanSyncPivotInAnAssociativeWay()
  {
    $this->seed();
    $user = PivotInteractionUser::first();
    $roles = PivotInteractionRoles::all();

    $user->roles()->syncWithAssocPivotValues($roles->pluck('id'), [
      ['office' => 'Dinning'],
      ['office' => 'Bedroom'],
      ['office' => 'Innsbruck'],
    ]);

    $this->assertEquals(3, $user->roles()->count());
    $this->assertEquals('Bedroom', $user->roles()->where('name', 'Sleeper')->first()->pivot->office);
  }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('roles');
        $this->schema()->drop('role_user');
    }
}

/**
 * User model
 */
class PivotInteractionUser extends Model
{
  protected $fillable = ['name'];
  protected $table = 'users';
  public $timestamps = false;
  public function roles()
  {
    return $this->belongsToMany(PivotInteractionRoles::class,'role_user','user_id','role_id')->withPivot('office');
  }
}

/**
 * Role model
 */

class PivotInteractionRoles extends Model
{
  public $timestamps = false;
  protected $fillable = ['name'];
  protected $table = 'roles';

  public function users()
  {
    return $this->belongsToMany(PivotInteractionUser::class,'role_user','role_id','user_id');
  }
}
