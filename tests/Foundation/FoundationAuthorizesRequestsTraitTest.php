<?php

use Illuminate\Container\Container;
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

        (new FoundationTestAuthorizeTraitClass)->authorize('foo');

        $this->assertTrue($_SERVER['_test.authorizes.trait']);
    }

    public function test_multiple_abilities_gate_check()
    {
        unset($_SERVER['_test.authorizes.trait']);

        $gate = $this->getBasicGate();

        $gate->define('foo', function () {
            $_SERVER['_test.authorizes.trait'] = true;

            return true;
        });

        $gate->define('bar', function () {
            return false;
        });

        (new FoundationTestAuthorizeTraitClass)->authorizeAny(['foo', 'bar']);

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

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function test_exception_is_thrown_if_gate_check_with_multiple_abilities_fails()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function () {
            return false;
        });

        $gate->define('bar', function () {
            return true;
        });

        (new FoundationTestAuthorizeTraitClass)->authorizeAll(['foo', 'bar']);
    }

    public function test_policies_may_be_called()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy(FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        (new FoundationTestAuthorizeTraitClass)->authorize('update', new FoundationAuthorizesRequestTestClass);

        $this->assertTrue($_SERVER['_test.authorizes.trait.policy']);
    }

    public function test_policy_method_may_be_guessed()
    {
        unset($_SERVER['_test.authorizes.trait.policy']);

        $gate = $this->getBasicGate();

        $gate->policy(FoundationAuthorizesRequestTestClass::class, FoundationAuthorizesRequestTestPolicy::class);

        (new FoundationTestAuthorizeTraitClass)->authorize([new FoundationAuthorizesRequestTestClass]);

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
