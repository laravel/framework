<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthAccessGateTest extends TestCase
{
    public function testBasicClosuresCanBeDefined()
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

    public function testBeforeCanTakeAnArrayCallbackAsObject()
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before([new AccessGateTestBeforeCallback, 'allowEverything']);

        $this->assertTrue($gate->check('anything'));
    }

    public function testBeforeCanTakeAnArrayCallbackAsObjectStatic()
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before([new AccessGateTestBeforeCallback, 'allowEverythingStatically']);

        $this->assertTrue($gate->check('anything'));
    }

    public function testBeforeCanTakeAnArrayCallbackWithStaticMethod()
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before([AccessGateTestBeforeCallback::class, 'allowEverythingStatically']);

        $this->assertTrue($gate->check('anything'));
    }

    public function testBeforeCanAllowGuests()
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before(function (?stdClass $user) {
            return true;
        });

        $this->assertTrue($gate->check('anything'));
    }

    public function testAfterCanAllowGuests()
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->after(function (?stdClass $user) {
            return true;
        });

        $this->assertTrue($gate->check('anything'));
    }

    public function testClosuresCanAllowGuestUsers()
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->define('foo', function (?stdClass $user) {
            return true;
        });

        $gate->define('bar', function (stdClass $user) {
            return false;
        });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
    }

    public function testPoliciesCanAllowGuests()
    {
        unset($_SERVER['__laravel.testBefore']);

        $gate = new Gate(new Container, function () {
            //
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

    public function testPolicyBeforeNotCalledWithGuestsIfItDoesntAllowThem()
    {
        $_SERVER['__laravel.testBefore'] = false;

        $gate = new Gate(new Container, function () {
            //
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNonGuestBefore::class);

        $this->assertTrue($gate->check('edit', new AccessGateTestDummy));
        $this->assertFalse($gate->check('update', new AccessGateTestDummy));
        $this->assertFalse($_SERVER['__laravel.testBefore']);

        unset($_SERVER['__laravel.testBefore']);
    }

    public function testBeforeAndAfterCallbacksCanAllowGuests()
    {
        $_SERVER['__laravel.gateBefore'] = false;
        $_SERVER['__laravel.gateBefore2'] = false;
        $_SERVER['__laravel.gateAfter'] = false;
        $_SERVER['__laravel.gateAfter2'] = false;

        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before(function (?stdClass $user) {
            $_SERVER['__laravel.gateBefore'] = true;
        });

        $gate->after(function (?stdClass $user) {
            $_SERVER['__laravel.gateAfter'] = true;
        });

        $gate->before(function (stdClass $user) {
            $_SERVER['__laravel.gateBefore2'] = true;
        });

        $gate->after(function (stdClass $user) {
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

        unset(
            $_SERVER['__laravel.gateBefore'],
            $_SERVER['__laravel.gateBefore2'],
            $_SERVER['__laravel.gateAfter'],
            $_SERVER['__laravel.gateAfter2']
        );
    }

    public function testResourceGatesCanBeDefined()
    {
        $gate = $this->getBasicGate();

        $gate->resource('test', AccessGateTestResource::class);

        $dummy = new AccessGateTestDummy;

        $this->assertTrue($gate->check('test.view'));
        $this->assertTrue($gate->check('test.create'));
        $this->assertTrue($gate->check('test.update', $dummy));
        $this->assertTrue($gate->check('test.delete', $dummy));
    }

    public function testCustomResourceGatesCanBeDefined()
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

    public function testBeforeCallbacksCanOverrideResultIfNecessary()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            return true;
        });
        $gate->before(function ($user, $ability) {
            $this->assertSame('foo', $ability);

            return false;
        });

        $this->assertFalse($gate->check('foo'));
    }

    public function testBeforeCallbacksDontInterruptGateCheckIfNoValueIsReturned()
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

    public function testAfterCallbacksAreCalledWithResult()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            return true;
        });

        $gate->define('bar', function ($user) {
            return false;
        });

        $gate->after(function ($user, $ability, $result) {
            if ($ability === 'foo') {
                $this->assertTrue($result, 'After callback on `foo` should receive true as result');
            } elseif ($ability === 'bar') {
                $this->assertFalse($result, 'After callback on `bar` should receive false as result');
            } else {
                $this->assertNull($result, 'After callback on `missing` should receive null as result');
            }
        });

        $this->assertTrue($gate->check('foo'));
        $this->assertFalse($gate->check('bar'));
        $this->assertFalse($gate->check('missing'));
    }

    public function testAfterCallbacksCanAllowIfNull()
    {
        $gate = $this->getBasicGate();

        $gate->after(function ($user, $ability, $result) {
            return true;
        });

        $this->assertTrue($gate->allows('null'));
    }

    public function testAfterCallbacksDoNotOverridePreviousResult()
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

    public function testAfterCallbacksDoNotOverrideEachOther()
    {
        $gate = $this->getBasicGate();

        $gate->after(function ($user, $ability, $result) {
            return $ability === 'allow';
        });

        $gate->after(function ($user, $ability, $result) {
            return ! $result;
        });

        $this->assertTrue($gate->allows('allow'));
        $this->assertTrue($gate->denies('deny'));
    }

    public function testCurrentUserThatIsOnGateAlwaysInjectedIntoClosureCallbacks()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            $this->assertSame(1, $user->id);

            return true;
        });

        $this->assertTrue($gate->check('foo'));
    }

    public function testASingleArgumentCanBePassedWhenCheckingAbilities()
    {
        $gate = $this->getBasicGate();

        $dummy = new AccessGateTestDummy;

        $gate->before(function ($user, $ability, array $arguments) use ($dummy) {
            $this->assertCount(1, $arguments);
            $this->assertSame($dummy, $arguments[0]);
        });

        $gate->define('foo', function ($user, $x) use ($dummy) {
            $this->assertSame($dummy, $x);

            return true;
        });

        $gate->after(function ($user, $ability, $result, array $arguments) use ($dummy) {
            $this->assertCount(1, $arguments);
            $this->assertSame($dummy, $arguments[0]);
        });

        $this->assertTrue($gate->check('foo', $dummy));
    }

    public function testMultipleArgumentsCanBePassedWhenCheckingAbilities()
    {
        $gate = $this->getBasicGate();

        $dummy1 = new AccessGateTestDummy;
        $dummy2 = new AccessGateTestDummy;

        $gate->before(function ($user, $ability, array $arguments) use ($dummy1, $dummy2) {
            $this->assertCount(2, $arguments);
            $this->assertSame([$dummy1, $dummy2], $arguments);
        });

        $gate->define('foo', function ($user, $x, $y) use ($dummy1, $dummy2) {
            $this->assertSame($dummy1, $x);
            $this->assertSame($dummy2, $y);

            return true;
        });

        $gate->after(function ($user, $ability, $result, array $arguments) use ($dummy1, $dummy2) {
            $this->assertCount(2, $arguments);
            $this->assertSame([$dummy1, $dummy2], $arguments);
        });

        $this->assertTrue($gate->check('foo', [$dummy1, $dummy2]));
    }

    public function testClassesCanBeDefinedAsCallbacksUsingAtNotation()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', AccessGateTestClass::class.'@foo');

        $this->assertTrue($gate->check('foo'));
    }

    public function testInvokableClassesCanBeDefined()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', AccessGateTestInvokableClass::class);

        $this->assertTrue($gate->check('foo'));
    }

    public function testGatesCanBeDefinedUsingAnArrayCallback()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', [new AccessGateTestStaticClass, 'foo']);

        $this->assertTrue($gate->check('foo'));
    }

    public function testGatesCanBeDefinedUsingAnArrayCallbackWithStaticMethod()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', [AccessGateTestStaticClass::class, 'foo']);

        $this->assertTrue($gate->check('foo'));
    }

    public function testPolicyClassesCanBeDefinedToHandleChecksForGivenType()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function testPolicyClassesHandleChecksForAllSubtypes()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestSubDummy));
    }

    public function testPolicyClassesHandleChecksForInterfaces()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummyInterface::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestSubDummy));
    }

    public function testPolicyConvertsDashToCamel()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update-dash', new AccessGateTestDummy));
    }

    public function testPolicyDefaultToFalseIfMethodDoesNotExistAndGateDoesNotExist()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertFalse($gate->check('nonexistent_method', new AccessGateTestDummy));
    }

    public function testPolicyClassesCanBeDefinedToHandleChecksForGivenClassName()
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('create', [AccessGateTestDummy::class, true]));
    }

    public function testPoliciesMayHaveBeforeMethodsToOverrideChecks()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithBefore::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function testPoliciesAlwaysOverrideClosuresWithSameName()
    {
        $gate = $this->getBasicGate();

        $gate->define('update', function () {
            $this->fail();
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function testPoliciesDeferToGatesIfMethodDoesNotExist()
    {
        $gate = $this->getBasicGate();

        $gate->define('nonexistent_method', function ($user) {
            return true;
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('nonexistent_method', new AccessGateTestDummy));
    }

    public function testForUserMethodAttachesANewUserToANewGateInstance()
    {
        $gate = $this->getBasicGate();

        // Assert that the callback receives the new user with ID of 2 instead of ID of 1...
        $gate->define('foo', function ($user) {
            $this->assertSame(2, $user->id);

            return true;
        });

        $this->assertTrue($gate->forUser((object) ['id' => 2])->check('foo'));
    }

    public function testForUserMethodAttachesANewUserToANewGateInstanceWithGuessCallback()
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function () {
            return true;
        });

        $counter = 0;
        $guesserCallback = function () use (&$counter) {
            $counter++;
        };
        $gate->guessPolicyNamesUsing($guesserCallback);
        $gate->getPolicyFor('fooClass');
        $this->assertSame(1, $counter);

        // now the guesser callback should be present on the new gate as well
        $newGate = $gate->forUser((object) ['id' => 1]);

        $newGate->getPolicyFor('fooClass');
        $this->assertSame(2, $counter);

        $newGate->getPolicyFor('fooClass');
        $this->assertSame(3, $counter);
    }

    /**
     * @dataProvider notCallableDataProvider
     */
    public function testDefineSecondParameterShouldBeStringOrCallable($callback)
    {
        $this->expectException(InvalidArgumentException::class);

        $gate = $this->getBasicGate();

        $gate->define('foo', $callback);
    }

    /**
     * @return array
     */
    public static function notCallableDataProvider()
    {
        return [
            [1],
            [new stdClass],
            [[]],
            [1.1],
        ];
    }

    public function testAuthorizeThrowsUnauthorizedException()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not an admin.');
        $this->expectExceptionCode(null);

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $gate->authorize('create', new AccessGateTestDummy);
    }

    public function testAuthorizeThrowsUnauthorizedExceptionWithCustomStatusCode()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Not allowed to view as it is not published.');
        $this->expectExceptionCode('unpublished');

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithCode::class);

        $gate->authorize('view', new AccessGateTestDummy);
    }

    public function testAuthorizeWithPolicyThatReturnsDeniedResponseObjectThrowsException()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Not allowed.');
        $this->expectExceptionCode('some_code');

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithDeniedResponseObject::class);

        $gate->authorize('create', new AccessGateTestDummy);
    }

    public function testPolicyThatThrowsAuthorizationExceptionIsCaughtInInspect()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyThrowingAuthorizationException::class);

        $response = $gate->inspect('create', new AccessGateTestDummy);

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('Not allowed.', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testAuthorizeReturnsAllowedResponse()
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $check = $gate->check('create', new AccessGateTestDummy);
        $response = $gate->authorize('create', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
        $this->assertTrue($check);
    }

    public function testResponseReturnsResponseWhenAbilityGranted()
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithCode::class);

        $response = $gate->inspect('view', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertNull($response->code());
    }

    public function testResponseReturnsResponseWhenAbilityDenied()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithCode::class);

        $response = $gate->inspect('view', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Not allowed to view as it is not published.', $response->message());
        $this->assertFalse($response->allowed());
        $this->assertTrue($response->denied());
        $this->assertSame('unpublished', $response->code());
    }

    public function testAuthorizeReturnsAnAllowedResponseForATruthyReturn()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $response = $gate->authorize('update', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
    }

    public function testAllowIfAuthorizesTrue()
    {
        $response = $this->getBasicGate()->allowIf(true);

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfAuthorizesTruthy()
    {
        $response = $this->getBasicGate()->allowIf('truthy');

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfAuthorizesIfGuest()
    {
        $response = $this->getBasicGate()->forUser(null)->allowIf(true);

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfAuthorizesCallbackTrue()
    {
        $response = $this->getBasicGate()->allowIf(function ($user) {
            $this->assertSame(1, $user->id);

            return true;
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testAllowIfAuthorizesResponseAllowed()
    {
        $response = $this->getBasicGate()->allowIf(Response::allow('foo', 'bar'));

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testAllowIfAuthorizesCallbackResponseAllowed()
    {
        $response = $this->getBasicGate()->allowIf(function () {
            return Response::allow('quz', 'qux');
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('quz', $response->message());
        $this->assertSame('qux', $response->code());
    }

    public function testAllowsIfCallbackAcceptsGuestsWhenAuthenticated()
    {
        $response = $this->getBasicGate()->allowIf(function (stdClass $user = null) {
            return $user !== null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfCallbackAcceptsGuestsWhenUnauthenticated()
    {
        $gate = $this->getBasicGate()->forUser(null);

        $response = $gate->allowIf(function (stdClass $user = null) {
            return $user === null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfThrowsExceptionWhenFalse()
    {
        $this->expectException(AuthorizationException::class);

        $this->getBasicGate()->allowIf(false);
    }

    public function testAllowIfThrowsExceptionWhenCallbackFalse()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->allowIf(function () {
            return false;
        }, 'foo', 'bar');
    }

    public function testAllowIfThrowsExceptionWhenResponseDenied()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->allowIf(Response::deny('foo', 'bar'));
    }

    public function testAllowIfThrowsExceptionWhenCallbackResponseDenied()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('quz');
        $this->expectExceptionCode('qux');

        $this->getBasicGate()->allowIf(function () {
            return Response::deny('quz', 'qux');
        }, 'foo', 'bar');
    }

    public function testAllowIfThrowsExceptionIfUnauthenticated()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->allowIf(function () {
            return true;
        }, 'foo', 'bar');
    }

    public function testAllowIfThrowsExceptionIfAuthUserExpectedWhenGuest()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->allowIf(function (stdClass $user) {
            return true;
        }, 'foo', 'bar');
    }

    public function testDenyIfAuthorizesFalse()
    {
        $response = $this->getBasicGate()->denyIf(false);

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfAuthorizesFalsy()
    {
        $response = $this->getBasicGate()->denyIf(0);

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfAuthorizesIfGuest()
    {
        $response = $this->getBasicGate()->forUser(null)->denyIf(false);

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfAuthorizesCallbackFalse()
    {
        $response = $this->getBasicGate()->denyIf(function ($user) {
            $this->assertSame(1, $user->id);

            return false;
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testDenyIfAuthorizesResponseAllowed()
    {
        $response = $this->getBasicGate()->denyIf(Response::allow('foo', 'bar'));

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testDenyIfAuthorizesCallbackResponseAllowed()
    {
        $response = $this->getBasicGate()->denyIf(function () {
            return Response::allow('quz', 'qux');
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('quz', $response->message());
        $this->assertSame('qux', $response->code());
    }

    public function testDenyIfCallbackAcceptsGuestsWhenAuthenticated()
    {
        $response = $this->getBasicGate()->denyIf(function (stdClass $user = null) {
            return $user === null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfCallbackAcceptsGuestsWhenUnauthenticated()
    {
        $gate = $this->getBasicGate()->forUser(null);

        $response = $gate->denyIf(function (stdClass $user = null) {
            return $user !== null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfThrowsExceptionWhenTrue()
    {
        $this->expectException(AuthorizationException::class);

        $this->getBasicGate()->denyIf(true);
    }

    public function testDenyIfThrowsExceptionWhenCallbackTrue()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->denyIf(function () {
            return true;
        }, 'foo', 'bar');
    }

    public function testDenyIfThrowsExceptionWhenResponseDenied()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->denyIf(Response::deny('foo', 'bar'));
    }

    public function testDenyIfThrowsExceptionWhenCallbackResponseDenied()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('quz');
        $this->expectExceptionCode('qux');

        $this->getBasicGate()->denyIf(function () {
            return Response::deny('quz', 'qux');
        }, 'foo', 'bar');
    }

    public function testDenyIfThrowsExceptionIfUnauthenticated()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->denyIf(function () {
            return false;
        }, 'foo', 'bar');
    }

    public function testDenyIfThrowsExceptionIfAuthUserExpectedWhenGuest()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->denyIf(function (stdClass $user) {
            return false;
        }, 'foo', 'bar');
    }

    protected function getBasicGate($isAdmin = false)
    {
        return new Gate(new Container, function () use ($isAdmin) {
            return (object) ['id' => 1, 'isAdmin' => $isAdmin];
        });
    }

    public function testAnyAbilityCheckPassesIfAllPass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testAnyAbilityCheckPassesIfAtLeastOnePasses()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithMixedPermissions::class);

        $this->assertTrue($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testAnyAbilityCheckFailsIfNonePass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertFalse($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testNoneAbilityCheckPassesIfAllFail()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertTrue($gate->none(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testEveryAbilityCheckPassesIfAllPass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testEveryAbilityCheckFailsIfAtLeastOneFails()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithMixedPermissions::class);

        $this->assertFalse($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testEveryAbilityCheckFailsIfNonePass()
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertFalse($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    /**
     * @dataProvider hasAbilitiesTestDataProvider
     *
     * @param  array  $abilitiesToSet
     * @param  array|string  $abilitiesToCheck
     * @param  bool  $expectedHasValue
     */
    public function testHasAbilities($abilitiesToSet, $abilitiesToCheck, $expectedHasValue)
    {
        $gate = $this->getBasicGate();

        $gate->resource('test', AccessGateTestResource::class, $abilitiesToSet);

        $this->assertEquals($expectedHasValue, $gate->has($abilitiesToCheck));
    }

    public static function hasAbilitiesTestDataProvider()
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

    public function testClassesCanBeDefinedAsCallbacksUsingAtNotationForGuests()
    {
        $gate = new Gate(new Container, function () {
            //
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
        $this->assertSame('foo was called', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('static_foo'));
        $this->assertSame('static foo was invoked', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('bar'));
        $this->assertSame('bar got invoked', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('static_@foo'));
        $this->assertSame('static foo was invoked', AccessGateTestClassForGuest::$calledMethod);

        $this->assertTrue($gate->check('invokable'));
        $this->assertSame('__invoke was called', AccessGateTestGuestInvokableClass::$calledMethod);

        $this->assertTrue($gate->check('nullable_invokable'));
        $this->assertSame('Nullable __invoke was called', AccessGateTestGuestNullableInvokable::$calledMethod);

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

    public function __invoke(?stdClass $user)
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
    public function before(?stdClass $user)
    {
        $_SERVER['__laravel.testBefore'] = true;
    }

    public function edit(?stdClass $user, AccessGateTestDummy $dummy)
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
    public function before(stdClass $user)
    {
        $_SERVER['__laravel.testBefore'] = true;
    }

    public function edit(?stdClass $user, AccessGateTestDummy $dummy)
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

class AccessGateTestPolicyWithCode
{
    use HandlesAuthorization;

    public function view($user)
    {
        if (! $user->isAdmin) {
            return $this->deny('Not allowed to view as it is not published.', 'unpublished');
        }

        return true;
    }
}

class AccessGateTestPolicyWithDeniedResponseObject
{
    public function create()
    {
        return Response::deny('Not allowed.', 'some_code');
    }
}

class AccessGateTestPolicyThrowingAuthorizationException
{
    public function create()
    {
        throw new AuthorizationException('Not allowed.', 'some_code');
    }
}
