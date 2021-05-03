<?php

namespace Illuminate\Tests\View;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use PHPUnit\Framework\TestCase;

class ViewPhpEngineTest extends TestCase
{
    public function testViewsMayBeProperlyRendered()
    {
        $engine = new PhpEngine(new Filesystem);
        $this->assertSame('Hello World
', $engine->get(__DIR__.'/fixtures/basic.php'));
    }
}
