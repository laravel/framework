<?php

namespace Illuminate\Tests\View\Blade;

class BladeAttributesTest extends AbstractBladeTestCase
{
    public function testAttributesAreCompiled()
    {
        $string = "<form @attributes(['id' => 'contactform', 'class' => 'bg-red', 'action' => '/contact', 'method' => 'POST', 'name' => false])></form>";
        $expected = "<form <?php echo (new \Illuminate\View\ComponentAttributeBag)(['id' => 'contactform', 'class' => 'bg-red', 'action' => '/contact', 'method' => 'POST', 'name' => false]); ?>></form>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
