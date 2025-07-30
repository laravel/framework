<?php

namespace Tests\Database;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Tests\Database\Fixtures\Models\User;

class DatabaseEloquentStrictRelationsTest extends TestCase
{
    public function testThrowsExceptionInStrictMode()
    {
        Config::set('database.connections.eloquent.strict_relationships', true);
        $this->expectException(RelationNotFoundException::class);
        User::has('invalid_relation')->get();
    }

    public function testNoExceptionInDefaultMode()
    {
        Config::set('database.connections.eloquent.strict_relationships', false);
        $users = User::has('invalid_relation')->get();
        $this->assertCount(0, $users);
    }

    public function testConsoleCommand()
    {
        $this->artisan('model:strict --enable')
            ->expectsOutput('Eloquent strict relationship mode enabled.');
    }
}
