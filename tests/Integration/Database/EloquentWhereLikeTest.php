<?php

namespace Illuminate\Tests\Integration\Database\EloquentWhereLikeTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentWhereLikeTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        User::create([
            'email' => 'test_email@laravel.com'
        ]);
        User::create([
            'email' => 'taylor.otwell@laravel.com'
        ]);
    }

    public function test_with_like()
    {
        $users = User::whereLike('email', 'otwell')->get();

        $this->assertEquals(['taylor.otwell@laravel.com'], $users->pluck('email')->all());
    }

    public function test_with_l_like()
    {
        $users = User::whereLLike('email', '@laravel.com')->get();

        $this->assertEquals(['test_email@laravel.com', 'taylor.otwell@laravel.com'], $users->pluck('email')->all());
    }

    public function test_with_r_like()
    {
        $users = User::whereRLike('email', 'taylor')->get();

        $this->assertEquals(['taylor.otwell@laravel.com'], $users->pluck('email')->all());
    }
}

class User extends Model
{
    public $timestamps = false;

    protected $fillable = ['email'];
}
