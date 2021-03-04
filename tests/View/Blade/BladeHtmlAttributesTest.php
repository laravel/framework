<?php

namespace Illuminate\Tests\View\Blade;

class BladeHtmlAttributesTest extends AbstractBladeTestCase
{
    public function testHtmlAttributesAreCompiled()
    {
        $this->assertSame('<?php echo \'class="flex"\'; ?>', (string) $this->compiler->compileString("@attributes(['class' => 'flex'])"));
        $this->assertSame('<?php echo \'class="flex"\'; ?>', (string) $this->compiler->compileString("@attributes('class', 'flex')"));
        $this->assertSame('<?php echo \'class="flex mt-2 mx-4"\'; ?>', (string) $this->compiler->compileString("@attributes('class', ['flex', 'mt-2', 'mx-4' => true, 'mx-2' => false])"));
        $this->assertSame('<?php echo \'disabled="disabled"\'; ?>', (string) $this->compiler->compileString("@attributes('disabled', true)"));
        $this->assertSame('<?php echo \'class="flex"\'; ?>', (string) $this->compiler->compileString("@attributes('class', ['flex' => true, 'grid' => false])"));
        $this->assertSame('<?php echo \'class="grid"\'; ?>', (string) $this->compiler->compileString("@attributes('class', ['flex' => false, 'grid' => true])"));
        $this->assertSame('<?php echo \'class="flex" disabled="disabled"\'; ?>', (string) $this->compiler->compileString("@attributes(['class' => ['flex' => true], 'disabled' => true])"));
        $this->assertSame('<?php echo \'class="flex" disabled="disabled"\'; ?>', (string) $this->compiler->compileString("@attributes(['class' => ['flex' => true], 'disabled'])"));
        $this->assertSame('<?php echo \'class="flex disabled" disabled="disabled"\'; ?>', (string) $this->compiler->compileString("@attributes(['class' => ['flex' => true, 'disabled' => true], 'disabled' => true])"));
        $this->assertSame('<?php echo \'class="flex selected" selected="selected"\'; ?>', (string) $this->compiler->compileString("@attributes(['class' => ['flex' => true, 'disabled' => false, 'selected' => true], 'disabled' => false, 'selected' => true])"));
        $this->assertSame('<?php echo \'class="flex p-4" method="POST"\'; ?>', (string) $this->compiler->compileString("@attributes(['class' => ['flex' => true, 'grid' => false, 'p-4'], 'method' => ['GET' => false, 'POST' => true]])"));
        $this->assertSame('<?php echo \'type="button"\'; ?>', (string) $this->compiler->compileString("@attributes('type', 'button')"));
        $this->assertSame('<?php echo \'type="submit"\'; ?>', (string) $this->compiler->compileString("@attributes('type', ['button' => false, 'submit' => true])"));
        $this->assertSame('<?php echo \'\'; ?>', (string) $this->compiler->compileString("@attributes('type', ['button' => false])"));
        $this->assertSame('<?php echo \'class="edit create"\'; ?>', (string) $this->compiler->compileString("@attributes('class', ['edit' => true, 'create' => true])"));
        $this->assertSame('<?php echo \'id="edit"\'; ?>', (string) $this->compiler->compileString("@attributes('id', ['edit' => true, 'create' => true])"));
        $this->assertSame('<?php echo \'id="create"\'; ?>', (string) $this->compiler->compileString("@attributes('id', ['edit' => false, 'create' => true, 'delete' => true])"));
        $this->assertSame('<?php echo \'id="create"\'; ?>', (string) $this->compiler->compileString("@attributes('id', ['edit' => false, 'create'])"));
        $this->assertSame('<?php echo \'id="create" class="edit create"\'; ?>', (string) $this->compiler->compileString("@attributes(['id' => ['edit' => false, 'create'], 'class' => ['edit' => true, 'create' => true]])"));
        $this->assertSame('<?php echo \'id="create" class="edit create"\'; ?>', (string) $this->compiler->compileString("@attributes(['id' => ['edit' => false, 'create'], 'class' => ['edit' => true, 'create' => true], 'wire:model' => ['foo' => false]])"));
    }
}
