<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\ConfirmHandlerInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ConfirmHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDefaultConfirmHelperProduction()
    {
        $mock = $this->setupMock('production');
        $mock->method('hasOption')->willReturn(false);
        $mock->expects($this->exactly(1))->method('alert')->with('Application In Production!');

        $this->assertSame(false, $mock->confirmToProceed());
    }

    public function testDefaultConfirmHelperLocal()
    {
        $mock = $this->setupMock('local');
        $mock->method('hasOption')->willReturn(false);
        $mock->expects($this->exactly(0))->method('alert');

        $this->assertSame(true, $mock->confirmToProceed());
    }

    public function testCustomConfirmHelper()
    {
        $handler = m::mock(ConfirmHandlerInterface::class);
        $handler->shouldReceive('warning')->andReturn('Application In Custom!');
        $handler->shouldReceive('handle')->andReturn(true);

        $mock = $this->setupMock('custom', $handler);
        $mock->method('hasOption')->willReturn(false);
        $mock->expects($this->exactly(1))->method('alert')->with('Application In Custom!');

        $this->assertSame(false, $mock->confirmToProceed());
    }

    protected function setupMock(string $env, $handler = null)
    {
        $app = new Application();
        $app['config'] = m::mock(Repository::class);
        $app['config']->shouldReceive('get')->with('console.confirm_handler')->andReturn($handler);
        $app['env'] = $env;

        $mock = $this->getMockForTrait(
            ConfirmableTrait::class,
            [],
            '',
            true,
            true,
            true,
            [
                'hasOption',
                'alert',
                'confirm',
                'comment',
            ]
        );
        $mock->laravel = $app;

        return $mock;
    }
}
