<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class FoundationAuthorizesRequestsTraitTest extends TestCase
{
    public function test_basic_gate_check()
    {
        unset($_SERVER['_test.authorizes.trait']);

        $gate = $this->getBasicGate();

        $gate->define('baz', function () {
            $_SERVER['_test.authorizes.trait'] = true;

            return true;
        });

        $response = (new FoundationTestAuthorizeTraitClass)->authorize('baz');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($_SERVER['_test.authorizes.trait']);
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     */
    public function test_exception_is_thrown_if_gate_check_fails()
    {
        $gate = $this->getBasicGate();

        $gate->define('baz', function () {
            return false;
        });

        (new FoundationTestAuthorizeTraitClass)->authorize('baz');
    }

    public function test_policies_may_be_called()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy(FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        $response = (new FoundationTestAuthorizeTraitClass)->authorize('update', new FoundationAuthorizesRequestTestClass);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($_SERVER['_test.authorizes.trait.policy']);
    }

    public function test_policy_method_may_be_guessed_passing_model_instance()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy(FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        $response = (new FoundationTestAuthorizeTraitClass)->authorize(new FoundationAuthorizesRequestTestClass);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($_SERVER['_test.authorizes.trait.policy']);
    }

    /**
     * @group something
     */
    public function test_policy_method_may_be_guessed_passing_class_name()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy('\\'.FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        $response = (new FoundationTestAuthorizeTraitClass)->authorize('\\'.FoundationAuthorizesRequestTestClass::class);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($_SERVER['_test.authorizes.trait.policy']);
    }

    public function test_policy_method_may_be_guessed_and_normalized()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy(FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        (new FoundationTestAuthorizeTraitClass)->store(new FoundationAuthorizesRequestTestClass);

        $this->assertTrue($_SERVER['_test.authorizes.trait.policy']);
    }

    public function getBasicGate()
    {
        $container = Container::setInstance(new Container);

        $gate = new Gate($container, function () {
            return (object) ['id' => 1];
        });

        $container->instance(GateContract::class, $gate);

        return $gate;
    }
}

class FoundationAuthorizesRequestTestClass
{
}

class FoundationAuthorizesRequestTestPolicy
{
    public function create()
    {
        $_SERVER['_test.authorizes.trait.policy'] = true;

        return true;
    }

    public function update()
    {
        $_SERVER['_test.authorizes.trait.policy'] = true;

        return true;
    }

    public function test_policy_method_may_be_guessed_passing_model_instance()
    {
        $_SERVER['_test.authorizes.trait.policy'] = true;

        return true;
    }

    public function test_policy_method_may_be_guessed_passing_class_name()
    {
        $_SERVER['_test.authorizes.trait.policy'] = true;

        return true;
    }
}

class FoundationTestAuthorizeTraitClass
{
    use AuthorizesRequests;

    public function store($object)
    {
        $this->authorize($object);
    }
}
