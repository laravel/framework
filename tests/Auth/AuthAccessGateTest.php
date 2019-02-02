<?php

namespace Illuminate\Tests\Auth;

use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthAccessGateTest extends TestCase
{
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

    public function test_before_can_take_an_array_callback_as_object()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->before([new AccessGateTestBeforeCallback, 'allowEverything']);

        $this->assertTrue($gate->check('anything'));
    }

    public function test_before_can_take_an_array_callback_as_object_static()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->before([new AccessGateTestBeforeCallback, 'allowEverythingStatically']);

        $this->assertTrue($gate->check('anything'));
    }

    public function test_before_can_take_an_array_callback_with_static_method()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->before([AccessGateTestBeforeCallback::class, 'allowEverythingStatically']);

        $this->assertTrue($gate->check('anything'));
    }

    public function test_before_can_allow_guests()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->before(function (?StdClass $user) {
            return true;
        });

        $this->assertTrue($gate->check('anything'));
    }

    public function test_after_can_allow_guests()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->after(function (?StdClass $user) {
            return true;
        });

        $this->assertTrue($gate->check('anything'));
    }

    public function test_closures_can_allow_guest_users()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->define('foo', function (?StdClass $user) {
            return true;
        });

        $gate->define('bar', function (StdClass $user) {
            return false;
        });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
    }

    public function test_policies_can_allow_guests()
    {
        unset($_SERVER['__laravel.testBefore']);

        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyThatAllowsGuests::class);

        $this->assertTrue($gate->check('edit', new AccessGateTestDummy));
        $this->assertFalse($gate->check('update', new AccessGateTestDummy));
        $this->assertTrue($_SERVER['__laravel.testBefore']);

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyThatAllowsGuests::class);

        $this->assertTrue($gate->check('edit', new AccessGateTestDummy));
        $this->assertTrue($gate->check('update', new AccessGateTestDummy));

        unset($_SERVER['__laravel.testBefore']);
    }

    public function test_policy_before_not_called_with_guests_if_it_doesnt_allow_them()
    {
        $_SERVER['__laravel.testBefore'] = false;

        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNonGuestBefore::class);

        $this->assertTrue($gate->check('edit', new AccessGateTestDummy));
        $this->assertFalse($gate->check('update', new AccessGateTestDummy));
        $this->assertFalse($_SERVER['__laravel.testBefore']);

        unset($_SERVER['__laravel.testBefore']);
    }

    public function test_before_and_after_callbacks_can_allow_guests()
    {
        $_SERVER['__laravel.gateBefore'] = false;
        $_SERVER['__laravel.gateBefore2'] = false;
        $_SERVER['__laravel.gateAfter'] = false;
        $_SERVER['__laravel.gateAfter2'] = false;

        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->before(function (?StdClass $user) {
            $_SERVER['__laravel.gateBefore'] = true;
        });

        $gate->after(function (?StdClass $user) {
            $_SERVER['__laravel.gateAfter'] = true;
        });

        $gate->before(function (StdClass $user) {
            $_SERVER['__laravel.gateBefore2'] = true;
        });

        $gate->after(function (StdClass $user) {
            $_SERVER['__laravel.gateAfter2'] = true;
        });

        $gate->define('foo', function ($user = null) {
            return true;
        });

        $this->assertTrue($gate->check('foo'));

        $this->assertTrue($_SERVER['__laravel.gateBefore']);
        $this->assertFalse($_SERVER['__laravel.gateBefore2']);
        $this->assertTrue($_SERVER['__laravel.gateAfter']);
        $this->assertFalse($_SERVER['__laravel.gateAfter2']);

        unset($_SERVER['__laravel.gateBefore']);
        unset($_SERVER['__laravel.gateBefore2']);
        unset($_SERVER['__laravel.gateAfter']);
        unset($_SERVER['__laravel.gateAfter2']);
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
            //
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
            } elseif ($ability == 'bar') {
                $this->assertFalse($result, 'After callback on `bar` should receive false as result');
            } else {
                $this->assertNull($result, 'After callback on `missing` should receive null as result');
            }
        });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
        $this->assertFalse($gate->check('missing'));
    }

    public function test_after_callbacks_can_allow_if_null()
    {
        $gate = $this->getBasicGate();

        $gate->after(function ($user, $ability, $result) {
            return true;
        });

        $this->assertTrue($gate->allows('null'));
    }

    public function test_after_callbacks_do_not_override_previous_result()
    {
        $gate = $this->getBasicGate();

        $gate->define('deny', function ($user) {
            return false;
        });

        $gate->define('allow', function ($user) {
            return true;
        });

        $gate->after(function ($user, $ability, $result) {
            return ! $result;
        });

        $this->assertTrue($gate->allows('allow'));
        $this->assertTrue($gate->denies('deny'));
    }

    public function test_after_callbacks_do_not_override_each_other()
    {
        $gate = $this->getBasicGate();

        $gate->after(function ($user, $ability, $result) {
            return $ability == 'allow';
        });

        $gate->after(function ($user, $ability, $result) {
            return ! $result;
        });

        $this->assertTrue($gate->allows('allow'));
        $this->assertTrue($gate->denies('deny'));
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

        $gate->define('foo', AccessGateTestClass::class.'@foo');

        $this->assertTrue($gate->check('foo'));
    }

    public function test_invokable_classes_can_be_defined()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', AccessGateTestInvokableClass::class);

        $this->assertTrue($gate->check('foo'));
    }

    public function test_gates_can_be_defined_using_an_array_callback()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', [new AccessGateTestStaticClass, 'foo']);

        $this->assertTrue($gate->check('foo'));
    }

    public function test_gates_can_be_defined_using_an_array_callback_with_static_method()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', [AccessGateTestStaticClass::class, 'foo']);

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

    public function test_policy_default_to_false_if_method_does_not_exist_and_gate_does_not_exist()
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

    public function test_policies_defer_to_gates_if_method_does_not_exist()
    {
        $gate = $this->getBasicGate();

        $gate->define('nonexistent_method', function ($user) {
            return true;
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('nonexistent_method', new AccessGateTestDummy));
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
     * @dataProvider notCallableDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function test_define_second_parameter_should_be_string_or_callable($callback)
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', $callback);
    }

    /**
     * @return array
     */
    public function notCallableDataProvider()
    {
        return [
            [1],
            [new stdClass],
            [[]],
            [1.1],
        ];
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     * @expectedExceptionMessage You are not an admin.
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

    public function test_any_ability_check_passes_if_all_pass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function test_any_ability_check_passes_if_at_least_one_passes()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithMixedPermissions::class);

        $this->assertTrue($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function test_any_ability_check_fails_if_none_pass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertFalse($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function test_every_ability_check_passes_if_all_pass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function test_every_ability_check_fails_if_at_least_one_fails()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithMixedPermissions::class);

        $this->assertFalse($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function test_every_ability_check_fails_if_none_pass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertFalse($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    /**
     * @dataProvider hasAbilitiesTestDataProvider
     *
     * @param array $abilitiesToSet
     * @param array|string $abilitiesToCheck
     * @param bool $expectedHasValue
     */
    public function test_has_abilities($abilitiesToSet, $abilitiesToCheck, $expectedHasValue)
    {
        $gate = $this->getBasicGate();

        $gate->resource('test', AccessGateTestResource::class, $abilitiesToSet);

        $this->assertEquals($expectedHasValue, $gate->has($abilitiesToCheck));
    }

    public function hasAbilitiesTestDataProvider()
    {
        $abilities = ['foo' => 'foo', 'bar' => 'bar'];
        $noAbilities = [];

        return [
            [$abilities, ['test.foo', 'test.bar'], true],
            [$abilities, ['test.bar', 'test.foo'], true],
            [$abilities, ['test.bar', 'test.foo', 'test.baz'], false],
            [$abilities, ['test.bar'], true],
            [$abilities, ['baz'], false],
            [$abilities, [''], false],
            [$abilities, [], true],
            [$abilities, 'test.bar', true],
            [$abilities, 'test.foo', true],
            [$abilities, '', false],
            [$noAbilities, '', false],
            [$noAbilities, [], true],
        ];
    }

    public function test_classes_can_be_defined_as_callbacks_using_at_notation_for_guests()
    {
        $gate = new Gate(new Container, function () {
            return null;
        });

        $gate->define('foo', AccessGateTestClassForGuest::class.'@foo');
        $gate->define('obj_foo', [new AccessGateTestClassForGuest, 'foo']);
        $gate->define('static_foo', [AccessGateTestClassForGuest::class, 'staticFoo']);
        $gate->define('static_@foo', AccessGateTestClassForGuest::class.'@staticFoo');
        $gate->define('bar', AccessGateTestClassForGuest::class.'@bar');
        $gate->define('invokable', AccessGateTestGuestInvokableClass::class);
        $gate->define('nullable_invokable', AccessGateTestGuestNullableInvokable::class);
        $gate->define('absent_invokable', 'someAbsentClass');

        AccessGateTestClassForGuest::$calledMethod = '';

        $this->assertTrue($gate->check('foo'));
        $this->assertEquals('foo was called', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('static_foo'));
        $this->assertEquals('static foo was invoked', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('bar'));
        $this->assertEquals('bar got invoked', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('static_@foo'));
        $this->assertEquals('static foo was invoked', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('invokable'));
        $this->assertEquals('__invoke was called', AccessGateTestGuestInvokableClass::$calledMethod);

        $this->assertTrue($gate->check('nullable_invokable'));
        $this->assertEquals('Nullable __invoke was called', AccessGateTestGuestNullableInvokable::$calledMethod);

        $this->assertFalse($gate->check('absent_invokable'));
    }
}

class AccessGateTestClassForGuest
{
    public static $calledMethod = null;

    public function foo($user = null)
    {
        static::$calledMethod = 'foo was called';

        return true;
    }

    public static function staticFoo($user = null)
    {
        static::$calledMethod = 'static foo was invoked';

        return true;
    }

    public function bar(?stdClass $user)
    {
        static::$calledMethod = 'bar got invoked';

        return true;
    }
}

class AccessGateTestStaticClass
{
    public static function foo($user)
    {
        return $user->id === 1;
    }
}

class AccessGateTestClass
{
    public function foo($user)
    {
        return $user->id === 1;
    }
}

class AccessGateTestInvokableClass
{
    public function __invoke($user)
    {
        return $user->id === 1;
    }
}

class AccessGateTestGuestInvokableClass
{
    public static $calledMethod = null;

    public function __invoke($user = null)
    {
        static::$calledMethod = '__invoke was called';

        return true;
    }
}

class AccessGateTestGuestNullableInvokable
{
    public static $calledMethod = null;

    public function __invoke(?StdClass $user)
    {
        static::$calledMethod = 'Nullable __invoke was called';

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
        return $user instanceof stdClass;
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

class AccessGateTestPolicyWithMixedPermissions
{
    public function edit($user, AccessGateTestDummy $dummy)
    {
        return false;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return true;
    }
}

class AccessGateTestPolicyWithNoPermissions
{
    public function edit($user, AccessGateTestDummy $dummy)
    {
        return false;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return false;
    }
}

class AccessGateTestPolicyWithAllPermissions
{
    public function edit($user, AccessGateTestDummy $dummy)
    {
        return true;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return true;
    }
}

class AccessGateTestPolicyThatAllowsGuests
{
    public function before(?StdClass $user)
    {
        $_SERVER['__laravel.testBefore'] = true;
    }

    public function edit(?StdClass $user, AccessGateTestDummy $dummy)
    {
        return true;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return true;
    }
}

class AccessGateTestPolicyWithNonGuestBefore
{
    public function before(StdClass $user)
    {
        $_SERVER['__laravel.testBefore'] = true;
    }

    public function edit(?StdClass $user, AccessGateTestDummy $dummy)
    {
        return true;
    }

    public function update($user, AccessGateTestDummy $dummy)
    {
        return true;
    }
}

class AccessGateTestBeforeCallback
{
    public function allowEverything($user = null)
    {
        return true;
    }

    public static function allowEverythingStatically($user = null)
    {
        return true;
    }
}
