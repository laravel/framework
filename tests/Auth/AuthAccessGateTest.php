<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

include_once 'Enums.php';

class AuthAccessGateTest extends TestCase
{
    public function testBasicClosuresCanBeDefined(): void
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

    public function testBeforeCanTakeAnArrayCallbackAsObject(): void
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before([new AccessGateTestBeforeCallback, 'allowEverything']);

        $this->assertTrue($gate->check('anything'));
    }

    public function testBeforeCanTakeAnArrayCallbackAsObjectStatic(): void
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before([new AccessGateTestBeforeCallback, 'allowEverythingStatically']);

        $this->assertTrue($gate->check('anything'));
    }

    public function testBeforeCanTakeAnArrayCallbackWithStaticMethod(): void
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before([AccessGateTestBeforeCallback::class, 'allowEverythingStatically']);

        $this->assertTrue($gate->check('anything'));
    }

    public function testBeforeCanAllowGuests(): void
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->before(function (?stdClass $user) {
            return true;
        });

        $this->assertTrue($gate->check('anything'));
    }

    public function testAfterCanAllowGuests(): void
    {
        $gate = new Gate(new Container, function () {
            //
        });

        $gate->after(function (?stdClass $user) {
            return true;
        });

        $this->assertTrue($gate->check('anything'));
    }

    public function testClosuresCanAllowGuestUsers(): void
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

    public function testPoliciesCanAllowGuests(): void
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

    public function testPolicyBeforeNotCalledWithGuestsIfItDoesntAllowThem(): void
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

    public function testBeforeAndAfterCallbacksCanAllowGuests(): void
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

    public function testResourceGatesCanBeDefined(): void
    {
        $gate = $this->getBasicGate();

        $gate->resource('test', AccessGateTestResource::class);

        $dummy = new AccessGateTestDummy;

        $this->assertTrue($gate->check('test.view'));
        $this->assertTrue($gate->check('test.create'));
        $this->assertTrue($gate->check('test.update', $dummy));
        $this->assertTrue($gate->check('test.delete', $dummy));
    }

    public function testCustomResourceGatesCanBeDefined(): void
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

    public function testBeforeCallbacksCanOverrideResultIfNecessary(): void
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

    public function testBeforeCallbacksDontInterruptGateCheckIfNoValueIsReturned(): void
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

    public function testAfterCallbacksAreCalledWithResult(): void
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

    public function testAfterCallbacksCanAllowIfNull(): void
    {
        $gate = $this->getBasicGate();

        $gate->after(function ($user, $ability, $result) {
            return true;
        });

        $this->assertTrue($gate->allows('null'));
    }

    public function testAfterCallbacksDoNotOverridePreviousResult(): void
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

    public function testAfterCallbacksDoNotOverrideEachOther(): void
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

    public function testCanDefineGatesUsingBackedEnum(): void
    {
        $gate = $this->getBasicGate();

        $gate->define(AbilitiesEnum::VIEW_DASHBOARD, function ($user) {
            return true;
        });

        $this->assertTrue($gate->allows('view-dashboard'));
    }

    public function testBackedEnumInAllows(): void
    {
        $gate = $this->getBasicGate();

        $gate->define(AbilitiesEnum::VIEW_DASHBOARD, function ($user) {
            return true;
        });

        $this->assertTrue($gate->allows(AbilitiesEnum::VIEW_DASHBOARD));
    }

    public function testBackedEnumInDenies(): void
    {
        $gate = $this->getBasicGate();

        $gate->define(AbilitiesEnum::VIEW_DASHBOARD, function ($user) {
            return false;
        });

        $this->assertTrue($gate->denies(AbilitiesEnum::VIEW_DASHBOARD));
    }

    public function testArrayAbilitiesInAllows(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('allow_1', function ($user) {
            return true;
        });
        $gate->define('allow_2', function ($user) {
            return true;
        });
        $gate->define(AbilitiesEnum::VIEW_DASHBOARD, function ($user) {
            return true;
        });
        $gate->define('deny', function ($user) {
            return false;
        });

        $this->assertTrue($gate->allows(['allow_1']));
        $this->assertTrue($gate->allows(['allow_1', 'allow_2', AbilitiesEnum::VIEW_DASHBOARD]));
        $this->assertFalse($gate->allows(['allow_1', 'allow_2', 'deny']));
        $this->assertFalse($gate->allows(['deny', 'allow_1', 'allow_2']));
    }

    public function testArrayAbilitiesInDenies(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('deny_1', function ($user) {
            return false;
        });
        $gate->define('deny_2', function ($user) {
            return false;
        });
        $gate->define(AbilitiesEnum::VIEW_DASHBOARD, function ($user) {
            return false;
        });
        $gate->define('allow', function ($user) {
            return true;
        });

        $this->assertTrue($gate->denies(['deny_1']));
        $this->assertTrue($gate->denies(['deny_1', 'deny_2', AbilitiesEnum::VIEW_DASHBOARD]));
        $this->assertTrue($gate->denies(['deny_1', 'allow']));
        $this->assertTrue($gate->denies(['allow', 'deny_1']));
        $this->assertFalse($gate->denies(['allow']));
    }

    public function testCurrentUserThatIsOnGateAlwaysInjectedIntoClosureCallbacks(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', function ($user) {
            $this->assertSame(1, $user->id);

            return true;
        });

        $this->assertTrue($gate->check('foo'));
    }

    public function testASingleArgumentCanBePassedWhenCheckingAbilities(): void
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

    public function testMultipleArgumentsCanBePassedWhenCheckingAbilities(): void
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

    public function testClassesCanBeDefinedAsCallbacksUsingAtNotation(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', AccessGateTestClass::class.'@foo');

        $this->assertTrue($gate->check('foo'));
    }

    public function testInvokableClassesCanBeDefined(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', AccessGateTestInvokableClass::class);

        $this->assertTrue($gate->check('foo'));
    }

    public function testGatesCanBeDefinedUsingAnArrayCallback(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', [new AccessGateTestStaticClass, 'foo']);

        $this->assertTrue($gate->check('foo'));
    }

    public function testGatesCanBeDefinedUsingAnArrayCallbackWithStaticMethod(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('foo', [AccessGateTestStaticClass::class, 'foo']);

        $this->assertTrue($gate->check('foo'));
    }

    public function testPolicyClassesCanBeDefinedToHandleChecksForGivenType(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function testPolicyClassesHandleChecksForAllSubtypes(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestSubDummy));
    }

    public function testPolicyClassesHandleChecksForInterfaces(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummyInterface::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestSubDummy));
    }

    public function testPolicyConvertsDashToCamel(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update-dash', new AccessGateTestDummy));
    }

    public function testPolicyDefaultToFalseIfMethodDoesNotExistAndGateDoesNotExist(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertFalse($gate->check('nonexistent_method', new AccessGateTestDummy));
    }

    public function testPolicyClassesCanBeDefinedToHandleChecksForGivenClassName(): void
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('create', [AccessGateTestDummy::class, true]));
    }

    public function testPoliciesMayHaveBeforeMethodsToOverrideChecks(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithBefore::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function testPoliciesAlwaysOverrideClosuresWithSameName(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('update', function () {
            $this->fail();
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('update', new AccessGateTestDummy));
    }

    public function testPoliciesDeferToGatesIfMethodDoesNotExist(): void
    {
        $gate = $this->getBasicGate();

        $gate->define('nonexistent_method', function ($user) {
            return true;
        });

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $this->assertTrue($gate->check('nonexistent_method', new AccessGateTestDummy));
    }

    public function testForUserMethodAttachesANewUserToANewGateInstance(): void
    {
        $gate = $this->getBasicGate();

        // Assert that the callback receives the new user with ID of 2 instead of ID of 1...
        $gate->define('foo', function ($user) {
            $this->assertSame(2, $user->id);

            return true;
        });

        $this->assertTrue($gate->forUser((object) ['id' => 2])->check('foo'));
    }

    public function testForUserMethodAttachesANewUserToANewGateInstanceWithGuessCallback(): void
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

    #[DataProvider('notCallableDataProvider')]
    public function testDefineSecondParameterShouldBeStringOrCallable($callback): void
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

    public function testAuthorizeThrowsUnauthorizedException(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not an admin.');
        $this->expectExceptionCode(0);

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $gate->authorize('create', new AccessGateTestDummy);
    }

    public function testAuthorizeThrowsUnauthorizedExceptionWithCustomStatusCode(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Not allowed to view as it is not published.');
        $this->expectExceptionCode('unpublished');

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithCode::class);

        $gate->authorize('view', new AccessGateTestDummy);
    }

    public function testAuthorizeWithPolicyThatReturnsDeniedResponseObjectThrowsException(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Not allowed.');
        $this->expectExceptionCode('some_code');

        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithDeniedResponseObject::class);

        $gate->authorize('create', new AccessGateTestDummy);
    }

    public function testPolicyThatThrowsAuthorizationExceptionIsCaughtInInspect(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyThrowingAuthorizationException::class);

        $response = $gate->inspect('create', new AccessGateTestDummy);

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('Not allowed.', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testAuthorizeReturnsAllowedResponse(): void
    {
        $gate = $this->getBasicGate(true);

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $check = $gate->check('create', new AccessGateTestDummy);
        $response = $gate->authorize('create', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
        $this->assertTrue($check);
    }

    public function testResponseReturnsResponseWhenAbilityGranted(): void
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

    public function testResponseReturnsResponseWhenAbilityDenied(): void
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

    public function testAuthorizeReturnsAnAllowedResponseForATruthyReturn(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicy::class);

        $response = $gate->authorize('update', new AccessGateTestDummy);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->message());
    }

    public function testAllowIfAuthorizesTrue(): void
    {
        $response = $this->getBasicGate()->allowIf(true);

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfAuthorizesTruthy(): void
    {
        $response = $this->getBasicGate()->allowIf('truthy');

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfAuthorizesIfGuest(): void
    {
        $response = $this->getBasicGate()->forUser(null)->allowIf(true);

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfAuthorizesCallbackTrue(): void
    {
        $response = $this->getBasicGate()->allowIf(function ($user) {
            $this->assertSame(1, $user->id);

            return true;
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testAllowIfAuthorizesResponseAllowed(): void
    {
        $response = $this->getBasicGate()->allowIf(Response::allow('foo', 'bar'));

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testAllowIfAuthorizesCallbackResponseAllowed(): void
    {
        $response = $this->getBasicGate()->allowIf(function () {
            return Response::allow('quz', 'qux');
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('quz', $response->message());
        $this->assertSame('qux', $response->code());
    }

    public function testAllowsIfCallbackAcceptsGuestsWhenAuthenticated(): void
    {
        $response = $this->getBasicGate()->allowIf(function (?stdClass $user = null) {
            return $user !== null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfCallbackAcceptsGuestsWhenUnauthenticated(): void
    {
        $gate = $this->getBasicGate()->forUser(null);

        $response = $gate->allowIf(function (?stdClass $user = null) {
            return $user === null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testAllowIfThrowsExceptionWhenFalse(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->getBasicGate()->allowIf(false);
    }

    public function testAllowIfThrowsExceptionWhenCallbackFalse(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->allowIf(function () {
            return false;
        }, 'foo', 'bar');
    }

    public function testAllowIfThrowsExceptionWhenResponseDenied(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->allowIf(Response::deny('foo', 'bar'));
    }

    public function testAllowIfThrowsExceptionWhenCallbackResponseDenied(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('quz');
        $this->expectExceptionCode('qux');

        $this->getBasicGate()->allowIf(function () {
            return Response::deny('quz', 'qux');
        }, 'foo', 'bar');
    }

    public function testAllowIfThrowsExceptionIfUnauthenticated(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->allowIf(function () {
            return true;
        }, 'foo', 'bar');
    }

    public function testAllowIfThrowsExceptionIfAuthUserExpectedWhenGuest(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->allowIf(function (stdClass $user) {
            return true;
        }, 'foo', 'bar');
    }

    public function testDenyIfAuthorizesFalse(): void
    {
        $response = $this->getBasicGate()->denyIf(false);

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfAuthorizesFalsy(): void
    {
        $response = $this->getBasicGate()->denyIf(0);

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfAuthorizesIfGuest(): void
    {
        $response = $this->getBasicGate()->forUser(null)->denyIf(false);

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfAuthorizesCallbackFalse(): void
    {
        $response = $this->getBasicGate()->denyIf(function ($user) {
            $this->assertSame(1, $user->id);

            return false;
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testDenyIfAuthorizesResponseAllowed(): void
    {
        $response = $this->getBasicGate()->denyIf(Response::allow('foo', 'bar'));

        $this->assertTrue($response->allowed());
        $this->assertSame('foo', $response->message());
        $this->assertSame('bar', $response->code());
    }

    public function testDenyIfAuthorizesCallbackResponseAllowed(): void
    {
        $response = $this->getBasicGate()->denyIf(function () {
            return Response::allow('quz', 'qux');
        }, 'foo', 'bar');

        $this->assertTrue($response->allowed());
        $this->assertSame('quz', $response->message());
        $this->assertSame('qux', $response->code());
    }

    public function testDenyIfCallbackAcceptsGuestsWhenAuthenticated(): void
    {
        $response = $this->getBasicGate()->denyIf(function (?stdClass $user = null) {
            return $user === null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfCallbackAcceptsGuestsWhenUnauthenticated(): void
    {
        $gate = $this->getBasicGate()->forUser(null);

        $response = $gate->denyIf(function (?stdClass $user = null) {
            return $user !== null;
        });

        $this->assertTrue($response->allowed());
    }

    public function testDenyIfThrowsExceptionWhenTrue(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->getBasicGate()->denyIf(true);
    }

    public function testDenyIfThrowsExceptionWhenCallbackTrue(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->denyIf(function () {
            return true;
        }, 'foo', 'bar');
    }

    public function testDenyIfThrowsExceptionWhenResponseDenied(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $this->getBasicGate()->denyIf(Response::deny('foo', 'bar'));
    }

    public function testDenyIfThrowsExceptionWhenCallbackResponseDenied(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('quz');
        $this->expectExceptionCode('qux');

        $this->getBasicGate()->denyIf(function () {
            return Response::deny('quz', 'qux');
        }, 'foo', 'bar');
    }

    public function testDenyIfThrowsExceptionIfUnauthenticated(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode('bar');

        $gate = $this->getBasicGate()->forUser(null);

        $gate->denyIf(function () {
            return false;
        }, 'foo', 'bar');
    }

    public function testDenyIfThrowsExceptionIfAuthUserExpectedWhenGuest(): void
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

    public function testAnyAbilityCheckPassesIfAllPass(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testAnyAbilityCheckPassesIfAtLeastOnePasses(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithMixedPermissions::class);

        $this->assertTrue($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testAnyAbilityCheckFailsIfNonePass(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertFalse($gate->any(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testNoneAbilityCheckPassesIfAllFail(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertTrue($gate->none(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testEveryAbilityCheckPassesIfAllPass(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testEveryAbilityCheckFailsIfAtLeastOneFails(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithMixedPermissions::class);

        $this->assertFalse($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testEveryAbilityCheckFailsIfNonePass(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertFalse($gate->check(['edit', 'update'], new AccessGateTestDummy));
    }

    public function testAnyAbilitiesCheckUsingBackedEnum(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->any(['edit', AbilitiesEnum::UPDATE], new AccessGateTestDummy));
    }

    public function testNoneAbilitiesCheckUsingBackedEnum(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithNoPermissions::class);

        $this->assertTrue($gate->none(['edit', AbilitiesEnum::UPDATE], new AccessGateTestDummy));
    }

    public function testAbilitiesCheckUsingBackedEnum(): void
    {
        $gate = $this->getBasicGate();

        $gate->policy(AccessGateTestDummy::class, AccessGateTestPolicyWithAllPermissions::class);

        $this->assertTrue($gate->check(['edit', AbilitiesEnum::UPDATE], new AccessGateTestDummy));
    }

    /**
     * @param  array  $abilitiesToSet
     * @param  array|string  $abilitiesToCheck
     * @param  bool  $expectedHasValue
     */
    #[DataProvider('hasAbilitiesTestDataProvider')]
    public function testHasAbilities($abilitiesToSet, $abilitiesToCheck, $expectedHasValue): void
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

    public function testClassesCanBeDefinedAsCallbacksUsingAtNotationForGuests(): void
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

    public function testCanSetDenialResponseInConstructor(): void
    {
        $gate = new Gate(container: new Container, userResolver: function () {
            //
        });

        $gate->defaultDenialResponse(Response::denyWithStatus(999, 'my_message', 'abc'));

        $gate->define('foo', function () {
            return false;
        });

        $response = $gate->inspect('foo', new AccessGateTestDummy);

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('my_message', $response->message());
        $this->assertSame('abc', $response->code());
        $this->assertSame(999, $response->status());
    }

    public function testCanSetDenialResponse(): void
    {
        $gate = new Gate(container: new Container, userResolver: function () {
            //
        });

        $gate->define('foo', function () {
            return false;
        });
        $gate->defaultDenialResponse(Response::denyWithStatus(404, 'not_found', 'xyz'));

        $response = $gate->inspect('foo', new AccessGateTestDummy);
        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('not_found', $response->message());
        $this->assertSame('xyz', $response->code());
        $this->assertSame(404, $response->status());
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
