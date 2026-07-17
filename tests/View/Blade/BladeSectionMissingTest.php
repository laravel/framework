<?php

namespace Illuminate\Tests\View\Blade;

class BladeSectionMissingTest extends AbstractBladeTestCase
{
    public function testSectionMissingStatementsAreCompiled()
    {
        $string = '@sectionMissing("section")
breeze
@endif';
        $expected = '<?php if (empty(trim($__env->yieldContent("section")))): ?>
breeze
<?php endif; ?>';
        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
