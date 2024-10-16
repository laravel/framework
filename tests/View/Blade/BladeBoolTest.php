<?php

namespace Illuminate\Tests\View\Blade;

class BladeBoolTest extends AbstractBladeTestCase
{
    public function testBool()
    {

        // For Javascript object{'isBool' : true}
        $string = "{'isBool' : @bool(true)}";
        $expected = "{'isBool' : <?php if(true): echo 'true'; else: 'false'; endif; ?>}";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // For Javascript object{'isBool' : false}
        $string = "{'isBool' : @bool(false)}";
        $expected = "{'isBool' : <?php if(false): echo 'true'; else: 'false'; endif; ?>}";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // For Alpine.js x-show attribute
        $string = "<input type='text' x-show='@bool(true)' />";
        $expected = "<input type='text' x-show='<?php if(true): echo 'true'; else: 'false'; endif; ?>' />";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // For Alpine.js x-show attribute
        $string = "<input type='text' x-show='@bool(false)' />";
        $expected = "<input type='text' x-show='<?php if(false): echo 'true'; else: 'false'; endif; ?>' />";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
