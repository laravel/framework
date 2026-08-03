<?php

namespace Illuminate\Tests\Foundation\Exceptions\Renderer\Mappers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BladeMapperTest extends TestCase
{
    public function testAddLineNumbersPrefixesEachMatchWithItsLineNumber(): void
    {
        $mapper = new BladeMapper(m::mock(Factory::class), m::mock(BladeCompiler::class));

        $value = "line one\n{{ \$foo }}\nline three\n{{ \$bar }}";

        $result = $this->addLineNumbers($mapper, $value, '/\{\{\s*(.+?)\s*\}\}/s');

        $this->assertSame(
            "line one\n|---LINE:2---|{{ \$foo }}\nline three\n|---LINE:4---|{{ \$bar }}",
            $result
        );
    }

    public function testAddLineNumbersReturnsOriginalValueUnchangedWhenNothingMatches(): void
    {
        $mapper = new BladeMapper(m::mock(Factory::class), m::mock(BladeCompiler::class));

        $value = 'plain text with no blade syntax';

        $result = $this->addLineNumbers($mapper, $value, '/\{\{\s*(.+?)\s*\}\}/s');

        $this->assertSame($value, $result);
    }

    public function testAddLineNumbersReturnsOriginalValueWhenThePatternFailsToCompile(): void
    {
        $mapper = new BladeMapper(m::mock(Factory::class), m::mock(BladeCompiler::class));

        $previousLimit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', 1);

        try {
            // Classic catastrophic-backtracking pattern: exhausts the backtrack
            // limit above and makes preg_replace_callback() return null.
            $value = str_repeat('a', 200).'!';

            $result = $this->addLineNumbers($mapper, $value, '/^(a+)+$/');

            $this->assertSame($value, $result);
        } finally {
            ini_set('pcre.backtrack_limit', $previousLimit);
        }
    }

    protected function addLineNumbers(BladeMapper $mapper, string $value, string $pattern): string
    {
        return (new ReflectionMethod($mapper, 'addLineNumbers'))->invoke($mapper, $value, $pattern);
    }
}
