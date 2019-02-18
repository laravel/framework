<?php

namespace Illuminate\Tests\Integration\Auth;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Illuminate\Tests\Integration\Auth\Fixtures\Policies\AuthenticationTestUserPolicy;

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
}
