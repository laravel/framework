<?php

namespace Illuminate\Tests\View;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\CompilerInterface;

class ViewCompilerEngineTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        m::close();
    }

    public function testViewsMayBeRecompiledAndRendered()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/Fixtures/foo.php')->andReturn(__DIR__.'/Fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->once()->with(__DIR__.'/Fixtures/foo.php')->andReturn(true);
        $engine->getCompiler()->shouldReceive('compile')->once()->with(__DIR__.'/Fixtures/foo.php');
        $results = $engine->get(__DIR__.'/Fixtures/foo.php');

        $this->assertEquals('Hello World
', $results);
    }

    public function testViewsAreNotRecompiledIfTheyAreNotExpired()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/Fixtures/foo.php')->andReturn(__DIR__.'/Fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->once()->andReturn(false);
        $engine->getCompiler()->shouldReceive('compile')->never();
        $results = $engine->get(__DIR__.'/Fixtures/foo.php');

        $this->assertEquals('Hello World
', $results);
    }

    protected function getEngine()
    {
        return new CompilerEngine(m::mock(CompilerInterface::class));
    }
}
