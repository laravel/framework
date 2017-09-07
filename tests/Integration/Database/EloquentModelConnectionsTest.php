<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentModelConnectionsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'conn1');

        $app['config']->set('database.connections.conn1', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('database.connections.conn2', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('parent', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('child', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parent_id');
        });

        Schema::connection('conn2')->create('parent', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::connection('conn2')->create('child', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parent_id');
        });
    }

    public function test_child_obeys_parent_connection()
    {
        $parent1 = ParentModel::create(['name' => str_random()]);
        $parent1->children()->create(['name' => 'childOnConn1']);
        $parents1 = ParentModel::with('children')->get();
        $this->assertEquals('childOnConn1', ChildModel::on('conn1')->first()->name);
        $this->assertEquals('childOnConn1', $parent1->children()->first()->name);
        $this->assertEquals('childOnConn1', $parents1[0]->children[0]->name);

        $parent2 = ParentModel::on('conn2')->create(['name' => str_random()]);
        $parent2->children()->create(['name' => 'childOnConn2']);
        $parents2 = ParentModel::on('conn2')->with('children')->get();
        $this->assertEquals('childOnConn2', ChildModel::on('conn2')->first()->name);
        $this->assertEquals('childOnConn2', $parent2->children()->first()->name);
        $this->assertEquals('childOnConn2', $parents2[0]->children[0]->name);
    }

    public function test_child_uses_its_own_connection_if_set()
    {
        $parent1 = ParentModel::create(['name' => str_random()]);
        $parent1->childrenDefaultConn2()->create(['name' => 'childAlwaysOnConn2']);
        $parents1 = ParentModel::with('childrenDefaultConn2')->get();
        $this->assertEquals('childAlwaysOnConn2', ChildModelDefaultConn2::first()->name);
        $this->assertEquals('childAlwaysOnConn2', $parent1->childrenDefaultConn2()->first()->name);
        $this->assertEquals('childAlwaysOnConn2', $parents1[0]->childrenDefaultConn2[0]->name);
        $this->assertEquals('childAlwaysOnConn2', $parents1[0]->childrenDefaultConn2[0]->name);
    }

    public function test_child_uses_its_own_connection_if_set_even_if_parent_explicit_connection()
    {
        $parent1 = ParentModel::on('conn1')->create(['name' => str_random()]);
        $parent1->childrenDefaultConn2()->create(['name' => 'childAlwaysOnConn2']);
        $parents1 = ParentModel::on('conn1')->with('childrenDefaultConn2')->get();
        $this->assertEquals('childAlwaysOnConn2', ChildModelDefaultConn2::first()->name);
        $this->assertEquals('childAlwaysOnConn2', $parent1->childrenDefaultConn2()->first()->name);
        $this->assertEquals('childAlwaysOnConn2', $parents1[0]->childrenDefaultConn2[0]->name);
    }
}

class ParentModel extends Model
{
    public $table = 'parent';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function children()
    {
        return $this->hasMany(ChildModel::class, 'parent_id');
    }

    public function childrenDefaultConn2()
    {
        return $this->hasMany(ChildModelDefaultConn2::class, 'parent_id');
    }
}

class ChildModel extends Model
{
    public $table = 'child';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}

class ChildModelDefaultConn2 extends Model
{
    public $connection = 'conn2';
    public $table = 'child';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}
