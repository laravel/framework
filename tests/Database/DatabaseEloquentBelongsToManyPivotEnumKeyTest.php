<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Tests\App\Enums\Bar;
use Illuminate\Tests\App\Models\Relationships\PivotEnumKeyTestRole;
use Illuminate\Tests\App\Models\Relationships\PivotEnumKeyTestUser;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyPivotEnumKeyTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema(): void
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->schema()->create('role_user', function ($table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
        });
    }

    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('roles');
        $this->schema()->drop('role_user');
    }

    public function testSyncAcceptsBackedEnumIds()
    {
        $user = PivotEnumKeyTestUser::create();
        PivotEnumKeyTestRole::insert([
            ['id' => 5, 'name' => 'editor'],
            ['id' => 6, 'name' => 'viewer'],
        ]);

        $changes = $user->roles()->sync([Bar::FOO, 6]);

        $this->assertSame([5, 6], $changes['attached']);
        $this->assertSame(
            ['editor', 'viewer'],
            $user->roles->pluck('name')->all()
        );
    }

    public function testToggleAcceptsBackedEnumIds()
    {
        $user = PivotEnumKeyTestUser::create();
        PivotEnumKeyTestRole::insert([
            ['id' => 5, 'name' => 'editor'],
        ]);

        $user->roles()->toggle([Bar::FOO]);

        $this->assertSame(['editor'], $user->fresh()->roles->pluck('name')->all());

        $user->roles()->toggle([Bar::FOO]);

        $this->assertSame([], $user->fresh()->roles->pluck('name')->all());
    }

    protected function connection(): \Illuminate\Database\ConnectionInterface
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}
