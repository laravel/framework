<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Orchestra\Testbench\TestCase;

#[RequiresDatabase('sqlite')]
class EloquentModelConnectionsTest extends TestCase
{
    protected function defineEnvironment($app)
    {
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

    protected function defineDatabaseMigrations()
    {
        Schema::create('parent', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('child', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parent_id');
        });

        Schema::connection('conn2')->create('parent', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::connection('conn2')->create('child', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parent_id');
        });
    }

    public function testChildObeysParentConnection()
    {
        $parent1 = ParentModel::create(['name' => Str::random()]);
        $parent1->children()->create(['name' => 'childOnConn1']);
        $parents1 = ParentModel::with('children')->get();
        $this->assertSame('childOnConn1', ChildModel::on('conn1')->first()->name);
        $this->assertSame('childOnConn1', $parent1->children()->first()->name);
        $this->assertSame('childOnConn1', $parents1[0]->children[0]->name);

        $parent2 = ParentModel::on('conn2')->create(['name' => Str::random()]);
        $parent2->children()->create(['name' => 'childOnConn2']);
        $parents2 = ParentModel::on('conn2')->with('children')->get();
        $this->assertSame('childOnConn2', ChildModel::on('conn2')->first()->name);
        $this->assertSame('childOnConn2', $parent2->children()->first()->name);
        $this->assertSame('childOnConn2', $parents2[0]->children[0]->name);
    }

    public function testChildUsesItsOwnConnectionIfSet()
    {
        $parent1 = ParentModel::create(['name' => Str::random()]);
        $parent1->childrenDefaultConn2()->create(['name' => 'childAlwaysOnConn2']);
        $parents1 = ParentModel::with('childrenDefaultConn2')->get();
        $this->assertSame('childAlwaysOnConn2', ChildModelDefaultConn2::first()->name);
        $this->assertSame('childAlwaysOnConn2', $parent1->childrenDefaultConn2()->first()->name);
        $this->assertSame('childAlwaysOnConn2', $parents1[0]->childrenDefaultConn2[0]->name);
        $this->assertSame('childAlwaysOnConn2', $parents1[0]->childrenDefaultConn2[0]->name);
    }

    public function testChildUsesItsOwnConnectionIfSetEvenIfParentExplicitConnection()
    {
        $parent1 = ParentModel::on('conn1')->create(['name' => Str::random()]);
        $parent1->childrenDefaultConn2()->create(['name' => 'childAlwaysOnConn2']);
        $parents1 = ParentModel::on('conn1')->with('childrenDefaultConn2')->get();
        $this->assertSame('childAlwaysOnConn2', ChildModelDefaultConn2::first()->name);
        $this->assertSame('childAlwaysOnConn2', $parent1->childrenDefaultConn2()->first()->name);
        $this->assertSame('childAlwaysOnConn2', $parents1[0]->childrenDefaultConn2[0]->name);
    }
}

class ParentModel extends Model
{
    public $table = 'parent';
    public $timestamps = false;
    protected $guarded = [];

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
    protected $guarded = [];

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
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}
