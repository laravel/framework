<?php

namespace Illuminate\Tests\Support;

use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Testing\Fakes\GateFake;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingGateFakeTest extends TestCase
{
    /** @var \Illuminate\Support\Testing\Fakes\GateFake */
    protected $fake;

    /** @var \Illuminate\Auth\Access\Gate */
    protected $originalGate;

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalGate = new Gate($this->container = new Container(), fn () => null);
        $this->fake = new GateFake($this->originalGate, $this->container);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testAllowsByDefault()
    {
        $response = $this->fake->allows('example');
        $this->assertTrue($response);

        $this->fake->fail('example');
        $response = $this->fake->allows('example');
        $this->assertFalse($response);
    }

    public function testDeniesFailsByDefault()
    {
        $response = $this->fake->denies('example');
        $this->assertFalse($response);

        $this->fake->fail('example');
        $response = $this->fake->denies('example');
        $this->assertTrue($response);
    }

    public function testCanProvideSequenceToFail()
    {
        $this->fake->fail('example', new Sequence(false, false, true));
        $response = $this->fake->allows('example');
        $this->assertFalse($response);

        $response = $this->fake->allows('example');
        $this->assertFalse($response);

        $response = $this->fake->allows('example');
        $this->assertTrue($response);
    }

    public function testWillCheckOriginalGateIfSpecified()
    {
        $hasBeenCalled = false;
        $this->originalGate->define('example', function (User $user = null) use (&$hasBeenCalled) {
            $hasBeenCalled = true;
        });

        $this->fake->checkOriginalGate()->allows('example');

        $this->assertTrue($hasBeenCalled);
    }

    public function testProvideResponseObjectAsResponse()
    {
        $this->fake->fail('example', Response::denyAsNotFound());

        $result = $this->fake->allows('example');

        $this->assertFalse($result);
    }

    public function testCanExcludeGateFromFake()
    {
        $this->originalGate->define('example', fn (User $user = null) => Response::denyAsNotFound());
        $this->fake->except('example');

        $response = $this->fake->raw('example');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->status());
    }

    public function testCanExcludePolicyFromFake()
    {
        $this->originalGate->policy(ModelStub::class, PolicyStub::class);
        $this->fake->except(PolicyStub::class, 'willFail');

        $response = $this->fake->raw('willFail', new ModelStub());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->status());
    }

    public function testCanAssertGateHasBeenCalled()
    {
        $this->fake->allows('example');

        $this->fake->assertChecked('example');

        $this->expectException(ExpectationFailedException::class);
        $this->fake->assertChecked('not-called');
    }

    public function testCanAssertGateHasBeenCalledWithArguments()
    {
        $this->fake->allows('example', 'test');
        $wasCalled = false;

        $this->fake->assertChecked('example', function ($argumentOne) use (&$wasCalled) {
            $this->assertEquals('test', $argumentOne);
            $wasCalled = true;

            return $argumentOne === 'test';
        });

        $this->assertTrue($wasCalled, 'The callback was not called');
    }

    public function testCanAssertGateHasBeenCalledAnAmountOfTimes()
    {
        $this->fake->allows('example', 'test');
        $this->fake->allows('example');

        $this->fake->assertCheckedTimes('example', 2);

        $this->expectException(ExpectationFailedException::class);
        $this->fake->assertCheckedTimes('example', 4);
    }

    public function testCanFailAPolicy()
    {
        $this->originalGate->policy(ModelStub::class, PolicyStub::class);

        $result = $this->fake->allows('update', new ModelStub());
        $this->assertTrue($result);

        $this->fake->fail(PolicyStub::class, 'update');

        $result = $this->fake->allows('update', new ModelStub());
        $this->assertFalse($result);
    }

    public function testCanFailAPolicyWithCustomResponse()
    {
        $this->originalGate->policy(ModelStub::class, PolicyStub::class);

        $this->fake->fail(PolicyStub::class, 'update', Response::denyAsNotFound());

        $result = $this->fake->raw('update', new ModelStub());

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(404, $result->status());
    }

    public function testCanCheckOriginalAbilityInPolicy()
    {
        $wasCalled = false;
        $class = new class ($wasCalled) {
            private $wasCalled;

            public function __construct(&$wasCalled)
            {
                $this->wasCalled = &$wasCalled;
            }

            public function update (?ModelStub $user, ModelStub $model)
            {
                $this->wasCalled = true;

                return true;
            }
        };

        $this->container->instance('FakePolicy', $class);
        $this->originalGate->policy(ModelStub::class, 'FakePolicy');

        $this->fake->checkOriginalGate()->fail('FakePolicy', 'update');

        $result = $this->fake->raw('update', new ModelStub());

        $this->assertFalse($result);
        $this->assertTrue($wasCalled);
    }

    public function testCanAssertPolicyWasCalled()
    {
        $this->originalGate->policy(ModelStub::class, PolicyStub::class);

        $this->fake->allows('update', $stub = new ModelStub());
        $wasCalled = false;

        $this->fake->assertChecked(PolicyStub::class, 'update', function ($argumentOne) use (&$wasCalled, $stub) {
            $this->assertEquals($stub, $argumentOne);
            $wasCalled = true;

            return true;
        });

        $this->assertTrue($wasCalled, 'The callback was not called');
    }

    public function testCanAssertPolicyWasCalledANumberOfTimes()
    {
        $this->originalGate->policy(ModelStub::class, PolicyStub::class);

        $this->fake->allows('update', $stub = new ModelStub());

        $this->fake->assertCheckedTimes(PolicyStub::class, 'update', 1);

        $this->expectException(ExpectationFailedException::class);
        $this->fake->assertCheckedTimes(PolicyStub::class, 'update', 2);
    }
}

class PolicyStub
{
    public function update(?ModelStub $user, ModelStub $model)
    {
        return true;
    }

    public function willFail(?ModelStub $user, ModelStub $model)
    {
        return Response::denyAsNotFound();
    }
}

class ModelStub {}
