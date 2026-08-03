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
    protected BladeMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new BladeMapper(m::mock(Factory::class), m::mock(BladeCompiler::class));
    }

    public function testAddLineNumbersPrefixesEachMatchWithItsLineNumber(): void
    {
        $value = "line one\n{{ \$foo }}\nline three\n{{ \$bar }}";

        $result = $this->addLineNumbers($value, '/\{\{\s*(.+?)\s*\}\}/s');

        $this->assertSame(
            "line one\n|---LINE:2---|{{ \$foo }}\nline three\n|---LINE:4---|{{ \$bar }}",
            $result
        );
    }

    public function testAddLineNumbersReturnsOriginalValueUnchangedWhenNothingMatches(): void
    {
        $value = 'plain text with no blade syntax';

        $result = $this->addLineNumbers($value, '/\{\{\s*(.+?)\s*\}\}/s');

        $this->assertSame($value, $result);
    }

    protected function addLineNumbers(string $value, string $pattern): string
    {
        return (new ReflectionMethod($this->mapper, 'addLineNumbers'))->invoke($this->mapper, $value, $pattern);
    }
}
