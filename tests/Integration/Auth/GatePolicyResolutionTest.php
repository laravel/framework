<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Illuminate\Tests\Integration\Auth\Fixtures\Models\Policies\Nested\SubTestUserPolicy;
use Illuminate\Tests\Integration\Auth\Fixtures\Policies\AuthenticationTestUserPolicy;
use Illuminate\Tests\Integration\Auth\Fixtures\Policies\Nested\TopTestUserPolicy;
use Orchestra\Testbench\TestCase;

class GatePolicyResolutionTest extends TestCase
{
    public function testGateEvaluationEventIsFired()
    {
        Event::fake();

        Gate::check('foo');

        Event::assertDispatched(GateEvaluated::class);
    }

    public function testPolicyCanBeGuessedUsingClassConventions()
    {
        $this->assertInstanceOf(
            AuthenticationTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );

        $this->assertInstanceOf(
            AuthenticationTestUserPolicy::class,
            Gate::getPolicyFor(Fixtures\Models\AuthenticationTestUser::class)
        );

        $this->assertNull(
            Gate::getPolicyFor(static::class)
        );
    }

    public function testPolicyCanBeGuessedForParallelClassHierarchies()
    {
        $this->assertInstanceOf(
            TopTestUserPolicy::class,
            Gate::getPolicyFor(Fixtures\Models\Nested\TopTestUser::class)
        );

        $this->assertInstanceOf(
            SubTestUserPolicy::class,
            Gate::getPolicyFor(Fixtures\Models\Nested\SubTestUser::class)
        );
    }

    public function testPolicyCanBeGuessedUsingCallback()
    {
        Gate::guessPolicyNamesUsing(function () {
            return AuthenticationTestUserPolicy::class;
        });

        $this->assertInstanceOf(
            AuthenticationTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );
    }

    public function testPolicyCanBeGuessedMultipleTimes()
    {
        Gate::guessPolicyNamesUsing(function () {
            return [
                'App\\Policies\\TestUserPolicy',
                AuthenticationTestUserPolicy::class,
            ];
        });

        $this->assertInstanceOf(
            AuthenticationTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );
    }

    public function testPolicyCanBeGivenByAttribute(): void
    {
        Gate::guessPolicyNamesUsing(fn () => [AuthenticationTestUserPolicy::class]);

        $this->assertInstanceOf(PostPolicy::class, Gate::getPolicyFor(Post::class));
    }
}

#[UsePolicy(PostPolicy::class)]
class Post extends Model
{
}

class PostPolicy
{
}
