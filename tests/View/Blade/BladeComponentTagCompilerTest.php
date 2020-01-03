<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\Compilers\ComponentTagCompiler;
use InvalidArgumentException;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    public function testSlotsCanBeCompiled()
    {
        $result = ComponentTagCompiler::compileSlots('<slot name="foo">
</slot>');

        $this->assertEquals("@slot('foo') \n @endslot", trim($result));
    }
}
