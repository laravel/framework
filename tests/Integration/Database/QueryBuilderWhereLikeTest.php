<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryBuilderWhereLikeTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 200);
            $table->text('email');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('users');
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('users')->insert([
            ['name' => 'John Doe', 'email' => 'John.Doe@example.com'],
            ['name' => 'Jane Doe', 'email' => 'janedoe@example.com'],
            ['name' => 'Dale doe', 'email' => 'Dale.Doe@example.com'],
            ['name' => 'Earl Smith', 'email' => 'Earl.Smith@example.com'],
            ['name' => 'tim smith', 'email' => 'tim.smith@example.com'],
        ]);
    }

    public function testWhereLike()
    {
        $users = DB::table('users')->whereLike('email', 'john.doe@example.com')->get();
        $this->assertCount(1, $users);
        $this->assertSame('John.Doe@example.com', $users[0]->email);

        $this->assertSame(4, DB::table('users')->whereNotLike('email', 'john.doe@example.com')->count());
    }

    public function testWhereLikeWithPercentWildcard()
    {
        $this->assertSame(5, DB::table('users')->whereLike('email', '%@example.com')->count());
        $this->assertSame(2, DB::table('users')->whereNotLike('email', '%Doe%')->count());

        $users = DB::table('users')->whereLike('email', 'john%')->get();
        $this->assertCount(1, $users);
        $this->assertSame('John.Doe@example.com', $users[0]->email);
    }

    public function testWhereLikeWithUnderscoreWildcard()
    {
        $users = DB::table('users')->whereLike('email', '_a_e_%@example.com')->get();
        $this->assertCount(2, $users);
        $this->assertSame('janedoe@example.com', $users[0]->email);
        $this->assertSame('Dale.Doe@example.com', $users[1]->email);
    }

    public function testWhereLikeCaseSensitive()
    {
        if ($this->driver === 'sqlsrv') {
            $this->markTestSkipped('The case-sensitive whereLike clause is not supported on MSSQL.');
        }

        $users = DB::table('users')->whereLike('email', 'john.doe@example.com', true)->get();
        $this->assertCount(0, $users);

        $users = DB::table('users')->whereLike('email', 'tim.smith@example.com', true)->get();
        $this->assertCount(1, $users);
        $this->assertSame('tim.smith@example.com', $users[0]->email);
        $this->assertSame(5, DB::table('users')->whereNotLike('email', 'john.doe@example.com', true)->count());
    }

    public function testWhereLikeWithPercentWildcardCaseSensitive()
    {
        if ($this->driver === 'sqlsrv') {
            $this->markTestSkipped('The case-sensitive whereLike clause is not supported on MSSQL.');
        }

        $this->assertSame(2, DB::table('users')->whereLike('email', '%Doe@example.com', true)->count());
        $this->assertSame(4, DB::table('users')->whereNotLike('email', '%smith%', true)->count());

        $users = DB::table('users')->whereLike('email', '%Doe@example.com', true)->get();
        $this->assertCount(2, $users);
        $this->assertSame('John.Doe@example.com', $users[0]->email);
        $this->assertSame('Dale.Doe@example.com', $users[1]->email);
    }

    public function testWhereLikeWithUnderscoreWildcardCaseSensitive()
    {
        if ($this->driver === 'sqlsrv') {
            $this->markTestSkipped('The case-sensitive whereLike clause is not supported on MSSQL.');
        }

        $users = DB::table('users')->whereLike('email', 'j__edoe@example.com', true)->get();
        $this->assertCount(1, $users);
        $this->assertSame('janedoe@example.com', $users[0]->email);

        $users = DB::table('users')->whereNotLike('email', '%_oe@example.com', true)->get();
        $this->assertCount(2, $users);
        $this->assertSame('Earl.Smith@example.com', $users[0]->email);
        $this->assertSame('tim.smith@example.com', $users[1]->email);
    }
}
