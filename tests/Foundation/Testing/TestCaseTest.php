<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Contracts\Foundation\Testing\DatabaseMigratable;
use Illuminate\Contracts\Foundation\Testing\DatabaseRefreshable;
use Illuminate\Contracts\Foundation\Testing\DatabaseTransactable;
use Illuminate\Contracts\Foundation\Testing\Fakeable;
use Illuminate\Contracts\Foundation\Testing\WithoutEvents as WithoutEventsContract;
use Illuminate\Contracts\Foundation\Testing\WithoutMiddleware as WithoutMiddlewareContract;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutEvents;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
use Mockery\Exception\InvalidCountException;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCaseTest extends PHPUnitTestCase
{
    /**
     * @return TestCaseWithInterfacesAndTraits|MockInterface
     */
    private function setUpTestCaseWithInterfaceAndTraits()
    {
        return m::mock(TestCaseWithInterfacesAndTraits::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('refreshDatabase')
            ->getMock()
            ->shouldReceive('runDatabaseMigrations')
            ->getMock()
            ->shouldReceive('disableMiddlewareForAllTests')
            ->getMock()
            ->shouldReceive('disableEventsForAllTests')
            ->getMock()
            ->shouldReceive('doSetUpFaker')
            ->getMock();
    }

    /**
     * @return TestCaseWithDatabaseTransactionsInterfaceAndTrait|MockInterface
     */
    private function setUpTestCaseWithDatabaseTransactionsInterfaceAndTrait()
    {
        return m::mock(TestCaseWithDatabaseTransactionsInterfaceAndTrait::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('beginDatabaseTransaction')
            ->getMock();
    }

    /**
     * @return TestCaseWithoutHelpers|MockInterface
     */
    private function setUpTestCaseWithoutHelpers()
    {
        return m::mock(TestCaseWithoutHelpers::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    /**
     * @return TestCaseWithImplementedMethods|MockInterface
     */
    private function setUpTestCaseWithImplementedMethods()
    {
        return m::mock(TestCaseWithImplementedMethods::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('refreshDatabase')
            ->getMock()
            ->shouldReceive('beginDatabaseTransaction')
            ->getMock()
            ->shouldReceive('runDatabaseMigrations')
            ->getMock()
            ->shouldReceive('disableMiddlewareForAllTests')
            ->getMock()
            ->shouldReceive('disableEventsForAllTests')
            ->getMock()
            ->shouldReceive('doSetUpFaker')
            ->getMock();
    }

    /**
     * @return TestCaseWithTraits|MockInterface
     */
    private function setUpTestCaseWithTraits()
    {
        return m::mock(TestCaseWithTraits::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('refreshDatabase')
            ->getMock()
            ->shouldReceive('runDatabaseMigrations')
            ->getMock()
            ->shouldReceive('disableMiddlewareForAllTests')
            ->getMock()
            ->shouldReceive('disableEventsForAllTests')
            ->getMock()
            ->shouldReceive('doSetUpFaker')
            ->getMock();
    }

    public function testSetUpTraitsWithClassUsingTraits()
    {
        $testCase = $this->setUpTestCaseWithInterfaceAndTraits();

        $testCase->runSetUpTraits();

        $this
            ->assertShouldHaveReceived($testCase, 'refreshDatabase')
            ->assertShouldHaveReceived($testCase, 'runDatabaseMigrations')
            ->assertShouldHaveReceived($testCase, 'disableMiddlewareForAllTests')
            ->assertShouldHaveReceived($testCase, 'disableEventsForAllTests')
            ->assertShouldHaveReceived($testCase, 'doSetUpFaker');
    }

    public function testSetUpTraitsWithClassUsingDatabaseTransactionTrait()
    {
        $testCase = $this->setUpTestCaseWithDatabaseTransactionsInterfaceAndTrait();

        $testCase->runSetUpTraits();

        $this->assertShouldHaveReceived($testCase, 'beginDatabaseTransaction');
    }

    public function testSetUpTraitsWithClassUsingNoTraits()
    {
        $testCase = $this->setUpTestCaseWithoutHelpers();

        $testCase->runSetUpTraits();

        $this
            ->assertShouldNotHaveReceived($testCase, 'refreshDatabase')
            ->assertShouldNotHaveReceived($testCase, 'runDatabaseMigrations')
            ->assertShouldNotHaveReceived($testCase, 'disableMiddlewareForAllTests')
            ->assertShouldNotHaveReceived($testCase, 'disableEventsForAllTests')
            ->assertShouldNotHaveReceived($testCase, 'doSetUpFaker')
            ->assertShouldNotHaveReceived($testCase, 'beginDatabaseTransaction');
    }

    public function testSetUpTraitsWithImplementedMethods()
    {
        $testCase = $this->setUpTestCaseWithImplementedMethods();

        $testCase->runSetUpTraits();

        $this
            ->assertShouldHaveReceived($testCase, 'refreshDatabase')
            ->assertShouldHaveReceived($testCase, 'runDatabaseMigrations')
            ->assertShouldHaveReceived($testCase, 'disableMiddlewareForAllTests')
            ->assertShouldHaveReceived($testCase, 'disableEventsForAllTests')
            ->assertShouldHaveReceived($testCase, 'doSetUpFaker')
            ->assertShouldHaveReceived($testCase, 'beginDatabaseTransaction');
    }

    public function testSetUpTraitsWithTraits()
    {
        $testCase = $this->setUpTestCaseWithTraits();

        $testCase->runSetUpTraits();

        $this
            ->assertShouldHaveReceived($testCase, 'refreshDatabase')
            ->assertShouldHaveReceived($testCase, 'runDatabaseMigrations')
            ->assertShouldHaveReceived($testCase, 'disableMiddlewareForAllTests')
            ->assertShouldHaveReceived($testCase, 'disableEventsForAllTests')
            ->assertShouldHaveReceived($testCase, 'doSetUpFaker');
    }

    private function assertShouldHaveReceived(MockInterface $testCase, $method)
    {
        try {
            $testCase->shouldHaveReceived($method)->once();

            $this->assertTrue(true);
        } catch (InvalidCountException $e) {
            $this->assertTrue(false);
        }

        return $this;
    }

    private function assertShouldNotHaveReceived(MockInterface $testCase, $method)
    {
        try {
            $testCase->shouldNotHaveReceived($method);

            $this->assertTrue(true);
        } catch (InvalidCountException $e) {
            $this->assertTrue(false);
        }

        return $this;
    }
}

class TestCaseWithInterfacesAndTraits extends TestCase implements
    DatabaseMigratable,
    DatabaseRefreshable,
    Fakeable,
    WithoutEventsContract,
    WithoutMiddlewareContract
{
    use RefreshDatabase;
    use DatabaseMigrations;
    use WithoutMiddleware;
    use WithoutEvents;
    use WithFaker;

    public function runSetUpTraits()
    {
        $this->setUpTraits();
    }

    public function createApplication()
    {
    }
}

class TestCaseWithDatabaseTransactionsInterfaceAndTrait extends TestCase implements DatabaseTransactable
{
    use DatabaseTransactions;

    public function runSetUpTraits()
    {
        $this->setUpTraits();
    }

    public function createApplication()
    {
    }
}

class TestCaseWithoutHelpers extends TestCase
{
    public function runSetUpTraits()
    {
        $this->setUpTraits();
    }

    public function createApplication()
    {
    }
}

class TestCaseWithImplementedMethods extends TestCase implements
    DatabaseMigratable,
    DatabaseRefreshable,
    DatabaseTransactable,
    Fakeable,
    WithoutEventsContract,
    WithoutMiddlewareContract
{
    public function runSetUpTraits()
    {
        $this->setUpTraits();
    }

    public function createApplication()
    {
    }

    public function runDatabaseMigrations()
    {
    }

    public function refreshDatabase()
    {
    }

    public function doSetUpFaker()
    {
    }

    public function disableEventsForAllTests()
    {
    }

    public function disableMiddlewareForAllTests()
    {
    }

    public function beginDatabaseTransaction()
    {
    }
}

class TestCaseWithTraits extends TestCase
{
    use RefreshDatabase;
    use DatabaseMigrations;
    use WithoutMiddleware;
    use WithoutEvents;
    use WithFaker;

    public function runSetUpTraits()
    {
        $this->setUpTraits();
    }

    public function createApplication()
    {
    }
}
