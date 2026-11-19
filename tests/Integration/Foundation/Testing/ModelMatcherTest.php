<?php

namespace Illuminate\Tests\Integration\Foundation\Testing\ModelMatcherTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\ModelMatcher;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class ModelMatcherTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    public function testItDoesNotMatchADifferentModel()
    {
        $taylor = User::create(['name' => 'Taylor']);
        $abigail = User::create(['name' => 'Abigail']);

        $this->assertFalse((new ModelMatcher($taylor))->match($abigail));
    }

    public function testItDoesNotMatchAValueThatIsNotAModel()
    {
        $user = User::create(['name' => 'Taylor']);
        $id = $user->id;

        $this->assertFalse((new ModelMatcher($user))->match($id));
    }

    public function testItMatchesAnotherInstanceOfTheSameModel()
    {
        $user = User::create(['name' => 'Taylor']);
        $found = User::find($user->id);

        $this->assertTrue((new ModelMatcher($user))->match($found));
    }
}

class User extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
