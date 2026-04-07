<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class TokenGuardAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'auth.guards.api' => [
                'driver' => 'token',
                'provider' => 'users',
                'hash' => true,
            ],
            'auth.providers.users.model' => AuthenticationTestUser::class,
        ]);
    }

    protected function afterRefreshingDatabase()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token')->nullable();
        });
    }

    public function testTokenGuardValidateAuthenticatesHashedApiTokens()
    {
        AuthenticationTestUser::create([
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => 'password',
            'api_token' => hash('sha256', 'plain-token'),
        ]);

        $this->assertTrue($this->app['auth']->guard('api')->validate([
            'api_token' => 'plain-token',
        ]));
    }
}
