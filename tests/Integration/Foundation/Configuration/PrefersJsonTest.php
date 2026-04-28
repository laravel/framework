<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Exception;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;

class PrefersJsonTest extends TestCase
{
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->prefersJsonResponses()
            ->create();
    }

    public function testArrayRouteReturnsJsonUnderWildcardAccept()
    {
        Route::get('payload', fn () => ['message' => 'hello']);

        $this->get('payload', ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'hello']);
    }

    public function testThrownExceptionRendersAsJsonUnderWildcardAccept()
    {
        Route::get('boom', fn () => throw new Exception('boom'));

        $this->get('boom', ['Accept' => '*/*'])
            ->assertInternalServerError()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    public function testUnauthenticatedRouteReturnsJsonUnderWildcardAccept()
    {
        Route::get('protected', fn () => 'secret')->middleware(Authenticate::class);

        $this->get('protected', ['Accept' => '*/*'])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function testRequirePasswordMiddlewareReturnsJsonUnderWildcardAccept()
    {
        Route::get('password-confirm', fn () => 'page')->name('password.confirm');

        Route::get('protected', fn () => 'secret')
            ->middleware([StartSession::class, RequirePassword::class]);

        $this->withSession(['auth.password_confirmed_at' => time() - 10801])
            ->get('protected', ['Accept' => '*/*'])
            ->assertStatus(423)
            ->assertJson(['message' => 'Password confirmation required.']);
    }

    public function testEnsureEmailIsVerifiedMiddlewareReturnsJsonUnderWildcardAccept()
    {
        Route::get('verification-notice', fn () => 'page')->name('verification.notice');

        $user = new UnverifiedUser;
        Auth::setUser($user);

        Route::get('verified-only', fn () => 'secret')
            ->middleware(EnsureEmailIsVerified::class);

        $this->actingAs($user)
            ->get('verified-only', ['Accept' => '*/*'])
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function testValidationExceptionRendersAsJsonUnderWildcardAccept()
    {
        Route::get('validate', function () {
            throw ValidationException::withMessages(['email' => 'The email field is required.']);
        });

        $this->get('validate', ['Accept' => '*/*'])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors(['email' => 'The email field is required.']);
    }

    public function testExplicitHtmlAcceptHeaderStillReceivesHtml()
    {
        Route::get('plain', fn () => 'hello');

        $this->get('plain', ['Accept' => 'text/html'])
            ->assertOk()
            ->assertSee('hello')
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}

class UnverifiedUser extends Authenticatable implements MustVerifyEmail
{
    protected $guarded = [];

    public function hasVerifiedEmail(): bool
    {
        return false;
    }

    public function markEmailAsVerified(): bool
    {
        return false;
    }

    public function sendEmailVerificationNotification(): void
    {
        //
    }

    public function getEmailForVerification(): string
    {
        return 'test@example.com';
    }

    public function getAuthIdentifier()
    {
        return 1;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthPassword()
    {
        return 'secret';
    }
}
