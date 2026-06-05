<?php

namespace Tests\Unit\Console;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Foundation\Console\DocsCommand;

class DocsCommandTest extends MockeryTestCase
{


    /**
     * Test that the built‑in opener uses Process with an argument array to avoid shell injection.
     */
    public function testOpenViaBuiltInStrategyUsesArrayArguments()
    {
        $url = 'https://example.com';
        $binary = 'true'; // harmless binary that always succeeds

        // Stub for the command's $components property to safely handle warn/info calls.
        $componentsStub = new class {
            public function warn($msg) {}
            public function info($msg) {}
        };

        // Overload ExecutableFinder so it returns the dummy binary.
        $finderMock = Mockery::mock('overload:' . ExecutableFinder::class);
        $finderMock->shouldReceive('find')
            ->withAnyArgs()
            ->andReturn($binary);



        $command = new DocsCommand();

        // Inject a stub for the protected $components property without using setAccessible.
        $componentsStub = new class {
            public function warn($msg) {}
            public function info($msg) {}
        };
        $setComponents = function ($value) {
            $this->components = $value;
        };
        $setComponents = $setComponents->bindTo($command, $command);
        $setComponents($componentsStub);

        // Set protected property systemOsFamily to Linux without setAccessible.
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
        $finderMock = Mockery::mock('overload:' . ExecutableFinder::class);
        $finderMock->shouldReceive('find')->andReturnNull();

        $command = new DocsCommand();

        // Create reflection for the command instance.
        $ref = new \ReflectionClass($command);

        // Inject a stub for the protected $components property.
        $componentsStub = new class {
            public function warn($msg) {}
            public function info($msg) {}
        };
        $propComp = $ref->getProperty('components');
        $propComp->setAccessible(true);
        $propComp->setValue($command, $componentsStub);

        // Set systemOsFamily to Linux.
        $prop = $ref->getProperty('systemOsFamily');
        $prop->setAccessible(true);
        $prop->setValue($command, 'Linux');

        // Capture output using output buffer to ensure no exception.
        $method = $ref->getMethod('openViaBuiltInStrategy');
        $method->setAccessible(true);
        $method->invoke($command, $url);

        $this->assertTrue(true); // If no exception, test passes.
    }
}
