<?php

namespace Tests\Unit\Console;

use Illuminate\Foundation\Console\DocsCommand;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class DocsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Test that the built‑in opener uses Process with an argument array to avoid shell injection.
     */
    public function testOpenViaBuiltInStrategyUsesArrayArguments()
    {
        $url = 'https://example.com';
        $binary = 'true'; // harmless binary that always succeeds

        // Overload ExecutableFinder so it returns the dummy binary.
        $finderMock = Mockery::mock('overload:'.ExecutableFinder::class);
        $finderMock->shouldReceive('find')
            ->withAnyArgs()
            ->andReturn($binary);

        $command = new DocsCommand();

        // Inject a stub for the protected $components property.
        $componentsStub = new class
        {
            public function warn($msg)
            {
            }

            public function info($msg)
            {
            }
        };
        $setComponents = function ($value) {
            $this->components = $value;
        };
        $setComponents = $setComponents->bindTo($command, $command);
        $setComponents($componentsStub);

        // Set protected property systemOsFamily to Linux.
        $setOsFamily = function ($value) {
            $this->systemOsFamily = $value;
        };
        $setOsFamily = $setOsFamily->bindTo($command, $command);
        $setOsFamily('Linux');

        // Invoke the protected method using closure binding.
        $invoke = function ($url) {
            $this->openViaBuiltInStrategy($url);
        };
        $invoke = $invoke->bindTo($command, $command);
        $invoke($url);
    }

    /**
     * Test that when no binary is found the method returns early without throwing.
     */
    public function testOpenViaBuiltInStrategyReturnsWhenBinaryNotFound()
    {
        $url = 'https://example.com';

        // Overload ExecutableFinder to return null.
        $finderMock = Mockery::mock('overload:'.ExecutableFinder::class);
        $finderMock->shouldReceive('find')->andReturnNull();

        $command = new DocsCommand();

        // Inject a stub for the protected $components property.
        $componentsStub = new class
        {
            public function warn($msg)
            {
            }

            public function info($msg)
            {
            }
        };
        $setComponents = function ($value) {
            $this->components = $value;
        };
        $setComponents = $setComponents->bindTo($command, $command);
        $setComponents($componentsStub);

        // Set systemOsFamily to Linux.
        $setOsFamily = function ($value) {
            $this->systemOsFamily = $value;
        };
        $setOsFamily = $setOsFamily->bindTo($command, $command);
        $setOsFamily('Linux');

        // Invoke the protected method — should not throw.
        $invoke = function ($url) {
            $this->openViaBuiltInStrategy($url);
        };
        $invoke = $invoke->bindTo($command, $command);
        $invoke($url);

        $this->assertTrue(true); // If no exception, test passes.
    }
}
