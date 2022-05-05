<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBlueprintTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Builder::$defaultMorphKeyType = 'int';
    }

    public function testIndexDefaultNames()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertSame('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testIndexDefaultNamesWhenPrefixSupplied()
    {
        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo', null, 'prefix_');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNames()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertSame('users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertSame('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNamesWhenPrefixSupplied()
    {
        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users', null, 'prefix_');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo', null, 'prefix_');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertSame('prefix_geo_coordinates_spatialindex', $commands[0]->index);
    }
}
