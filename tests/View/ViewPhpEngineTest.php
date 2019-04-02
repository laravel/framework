<?php

namespace Illuminate\Tests\View;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Engines\PhpEngine;

class ViewPhpEngineTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testViewsMayBeProperlyRendered()
    {
        $engine = new PhpEngine;
        $this->assertEquals('Hello World
', $engine->get(__DIR__.'/fixtures/basic.php'));
    }

    public function testTrimWhitespace()
    {
        $engine = new PhpEngine;
        $this->assertEquals('Hello World', $engine->get(__DIR__.'/fixtures/basicWithPath.php'));
    }

    public function testTrimOnlyPathRelatedWhitespace()
    {
        $engine = new PhpEngine;
        $this->assertEquals('Hello World


', $engine->get(__DIR__.'/fixtures/basicWithManyWhiteSpacesBeforePath.php'));
    }

    public function testEmpty()
    {
        $engine = new PhpEngine;
        $this->assertEquals('', $engine->get(__DIR__.'/fixtures/empty.php'));
    }
}
