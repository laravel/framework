<?php

namespace Illuminate\Tests\View\Blade;

class BladeDuskTest extends AbstractBladeTestCase
{
    public function testDuskIsCompiled()
    {
        $this->assertEquals(
            "<?php echo app()->environment('testing', 'local') ? 'dusk=\"foo\"' : ''; ?>",
            $this->compiler->compileString("@dusk('foo')")
        );
        $this->assertEquals(
            "<?php echo app()->environment('testing', 'local') ? 'dusk=\"foo\"' : ''; ?>",
            $this->compiler->compileString("@dusk(\"foo\")")
        );
        $this->assertEquals(
            "<?php echo app()->environment('testing', 'local', 'staging') ? 'dusk=\"foo\"' : ''; ?>",
            $this->compiler->compileString("@dusk('foo', 'staging')")
        );
        $this->assertEquals(
            "<?php echo app()->environment('testing', 'local', 'staging') ? 'dusk=\"foo\"' : ''; ?>",
            $this->compiler->compileString("@dusk('foo', \"staging\")")
        );
        $this->assertEquals(
            "<?php echo app()->environment('testing', 'local', 'staging', 'production') ? 'dusk=\"foo\"' : ''; ?>",
            $this->compiler->compileString("@dusk('foo', 'staging', 'production')")
        );
    }
}
