<?php

use Illuminate\Container\Container;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;

class FoundationAuthorizesRequestsTraitTest extends PHPUnit_Framework_TestCase
{
    public function test_basic_gate_check()
    {
        unset($_SERVER['_test.authorizes.trait']);

        $gate = $this->getBasicGate();

        $gate->define('foo', function () {
            $_SERVER['_test.authorizes.trait'] = true;

            return true;
        });

        $response = (new FoundationTestAuthorizeTraitClass)->authorize('foo');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($_SERVER['_test.authorizes.trait']);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function test_exception_is_thrown_if_gate_check_fails()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function () {
            return false;
        });

        (new FoundationTestAuthorizeTraitClass)->authorize('foo');
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

    public function test_policy_method_may_be_guessed()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy(FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        $response = (new FoundationTestAuthorizeTraitClass)->authorize([new FoundationAuthorizesRequestTestClass]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($_SERVER['_test.authorizes.trait.policy']);
    }

    public function getBasicGate()
    {
        $container = new Container;
        Container::setInstance($container);

        $gate = new Illuminate\Auth\Access\Gate($container, function () { return (object) ['id' => 1]; });
        $container->instance(Gate::class, $gate);

        return $gate;
    }
}

class FoundationAuthorizesRequestTestClass
{
}

class FoundationAuthorizesRequestTestPolicy
{
    public function update()
    {
        $_SERVER['_test.authorizes.trait.policy'] = true;

        return true;
    }

    public function test_policy_method_may_be_guessed()
    {
        $_SERVER['_test.authorizes.trait.policy'] = true;

        return true;
    }
}

class FoundationTestAuthorizeTraitClass
{
    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
}
