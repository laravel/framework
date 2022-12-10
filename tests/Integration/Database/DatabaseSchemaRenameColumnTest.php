<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class DatabaseSchemaRenameColumnTest extends DatabaseTestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']['database.connections.sqlite.database'] = ':memory:';
        $app['config']['database.dbal.rename_column'] = false;
    }

    public function testRenameColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->string('foo');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->renameColumn('foo', 'bar');
        });

        $this->assertFalse(Schema::hasColumn('test', 'foo'));
        $this->assertTrue(Schema::hasColumn('test', 'bar'));
    }
}
