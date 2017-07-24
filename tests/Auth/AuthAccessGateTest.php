<?php

namespace Illuminate\Tests\Auth;

use StdClass;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class GateTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function test_gate_throws_exception_on_invalid_callback_type()
    {
        $this->getBasicGate()->define('foo', 'foo');
    }

    public function test_basic_closures_can_be_defined()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            return true;
        });
        $gate->define('bar', function ($user) {
            return false;
        });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
    }

    public function test_resource_gates_can_be_defined()
    {
        $gate = $this->getBasicGate();

        $gate->resource('test', AccessGateTestResource::class);

        $dummy = new AccessGateTestDummy;

        $this->assertTrue($gate->check('test.view'));
        $this->assertTrue($gate->check('test.create'));
        $this->assertTrue($gate->check('test.update', $dummy));
        $this->assertTrue($gate->check('test.delete', $dummy));
    }

    public function test_custom_resource_gates_can_be_defined()
    {
        $gate = $this->getBasicGate();

        $abilities = [
            'ability1' => 'foo',
            'ability2' => 'bar',
        ];

        $gate->resource('test', AccessGateTestCustomResource::class, $abilities);

        $this->assertTrue($gate->check('test.ability1'));
        $this->assertTrue($gate->check('test.ability2'));
    }

    public function test_before_callbacks_can_override_result_if_necessary()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            return true;
        });
        $gate->before(function ($user, $ability) {
            $this->assertEquals('foo', $ability);

            return false;
        });

        $this->assertFalse($gate->check('foo'));
    }

    public function test_before_callbacks_dont_interrupt_gate_check_if_no_value_is_returned()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            return true;
        });
        $gate->before(function () {
        });

        $this->assertTrue($gate->check('foo'));
    }

    public function test_after_callbacks_are_called_with_result()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            return true;
        });
        $gate->define('bar', function ($user) {
            return false;
        });

        $gate->after(function ($user, $ability, $result) {
            if ($ability == 'foo') {
                $this->assertTrue($result, 'After callback on `foo` should receive true as result');
            } else {
                $this->assertFalse($result, 'After callback on `bar` or `missing` should receive false as result');
            }
        });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
        $this->assertFalse($gate->check('missing'));
    }

    public function test_current_user_that_is_on_gate_always_injected_into_closure_callbacks()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            $this->assertEquals(1, $user->id);

            return true;
        });

        $this->assertTrue($gate->check('foo'));
    }

    public function test_a_single_argument_can_be_passed_when_checking_abilities()
    {
        $gate = $this->getBasicGate();

        $dummy = new AccessGateTestDummy;

        $gate->define('foo', function ($user, $x) use ($dummy) {
            $this->assertEquals($dummy, $x);

            return true;
        });

        $this->assertTrue($gate->check('foo', $dummy));
    }

    public function test_multiple_arguments_can_be_passed_when_checking_abilities()
    {
        $gate = $this->getBasicGate();

        $dummy1 = new AccessGateTestDummy;
        $dummy2 = new AccessGateTestDummy;

        $gate->define('foo', function ($user, $x, $y) use ($dummy1, $dummy2) {
            $this->assertEquals($dummy1, $x);
            $this->assertEquals($dummy2, $y);

            return true;
        });

        $this->assertTrue($gate->check('foo', [$dummy1, $dummy2]));
    }

    public function test_classes_can_be_defined_as_callbacks_using_at_notation()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', '\Illuminate\Tests\Auth\AccessGateTestClass@foo');

        $this->assertTrue($gate->check('foo'));
    }

    public function test_policy_classes_can_be_defined_to_handle_checks_for_given_type()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function test_policy_classes_handle_checks_for_all_subtypes()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestSubDummy));
    }

    public function test_policy_classes_handle_checks_for_interfaces()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummyInterface::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestSubDummy));
    }

    public function test_policy_converts_dash_to_camel()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update-dash', new AccessGateTestDummy));
    }

    public function test_policy_default_to_false_if_method_does_not_exist()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertFalse($gate->check('nonexistent_method', new AccessGateTestDummy));
    }

    public function test_policy_classes_can_be_defined_to_handle_checks_for_given_class_name()
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('create', [AccessGateTestDummy::class, true]));
    }

    public function test_policies_may_have_before_methods_to_override_checks()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithBefore::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function test_policies_always_override_closures_with_same_name()
    {
        $gate = $this->getBasicGate();

        $gate->define('update', function () {
            $this->fail();
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function test_for_user_method_attaches_a_new_user_to_a_new_gate_instance()
    {
        $gate = $this->getBasicGate();

        // Assert that the callback receives the new user with ID of 2 instead of ID of 1...
        $gate->define('foo', function ($user) {
            $this->assertEquals(2, $user->id);

            return true;
        });

        $this->assertTrue($gate->forUser((object) ['id' => 2])->check('foo'));
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     */
    public function test_authorize_throws_unauthorized_exception()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $gate->authorize('create', new AccessGateTestDummy);
    }

    public function test_authorize_returns_allowed_response()
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $check = $gate->check('create', new AccessGateTestDummy);
        $response = $gate->authorize('create', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
        $this->assertTrue($check);
    }

    public function test_authorize_returns_an_allowed_response_for_a_truthy_return()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $response = $gate->authorize('update', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
    }

    protected function getBasicGate($isAdmin = false)
    {
        return new Gate(new Container, function () use ($isAdmin) {
            return (object) ['id' => 1, 'isAdmin' => $isAdmin];
        });
    }
}

class AccessGateTestClass
{
    public function foo()
    {
        return true;
    }
}

interface AccessGateTestDummyInterface
{
    //
}

class AccessGateTestDummy implements AccessGateTestDummyInterface
{
    //
}

class AccessGateTestSubDummy extends AccessGateTestDummy
{
    //
}

class AccessGateTestPolicy
{
    use HandlesAuthorization;

    public function createAny($user, $additional)
    {
        return $additional;
    }

    public function create($user)
    {
        return $user->isAdmin ? $this->allow() : $this->deny('You are not an admin.');
    }

    public function updateAny($user, AccessGateTestDummy $dummy)
    {
        return ! $user->isAdmin;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return ! $user->isAdmin;
    }

    public function updateDash($user, AccessGateTestDummy $dummy)
    {
        return $user instanceof StdClass;
    }
}

class AccessGateTestPolicyWithBefore
{
    public function before($user, $ability)
    {
        return true;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return false;
    }
}

class AccessGateTestResource
{
    public function view($user)
    {
        return true;
    }

    public function create($user)
    {
        return true;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return true;
    }

    public function delete($user, AccessGateTestDummy $dummy)
    {
        return true;
    }
}

class AccessGateTestCustomResource
{
    public function foo($user)
    {
        return true;
    }

    public function bar($user)
    {
        return true;
    }
}
