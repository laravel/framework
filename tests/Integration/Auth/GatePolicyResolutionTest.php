<?php

namespace Illuminate\Tests\Integration\Auth;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Illuminate\Tests\Integration\Auth\Fixtures\Policies\AuthenticationTestUserPolicy;
use Illuminate\Tests\Integration\Auth\Fixtures\Policies\AuthenticationCustomTestUserPolicy;

/**
 * @group integration
 */
class GatePolicyResolutionTest extends TestCase
{
    public function testPolicyCanBeGuessedUsingClassConventions()
    {
        $this->assertInstanceOf(
            AuthenticationTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );
    }

    public function testPolicyCanBeGuessedUsingCallback()
    {
        Gate::guessPolicyNamesUsing(function () {
            return AuthenticationCustomTestUserPolicy::class;
        });

        $this->assertInstanceOf(
            AuthenticationCustomTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );
    }

    public function testPolicyCanBeGuessedMultipleTimes()
    {
        Gate::guessPolicyNamesUsing(function () {
            return [
                'App\\Policies\\TestUserPolicy',
                AuthenticationCustomTestUserPolicy::class,
            ];
        });

        $this->assertInstanceOf(
            AuthenticationCustomTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );
    }

    public function testDefaultPolicyGuessWillBeMergedWithCallbackGuesses()
    {
        Gate::guessPolicyNamesUsing(function () {
            return 'App\\Policies\\TestUserPolicy';
        });

        $this->assertInstanceOf(
            AuthenticationTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );

        Gate::guessPolicyNamesUsing(function () {
            return AuthenticationCustomTestUserPolicy::class;
        });

        $this->assertInstanceOf(
            AuthenticationCustomTestUserPolicy::class,
            Gate::getPolicyFor(AuthenticationTestUser::class)
        );
    }
}
