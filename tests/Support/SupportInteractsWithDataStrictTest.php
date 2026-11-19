<?php

namespace Illuminate\Tests\Support;

use Illuminate\Console\CommandInput;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Uri;
use Illuminate\Support\UriQueryString;
use Illuminate\Support\ValidatedInput;
use Illuminate\View\ComponentAttributeBag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SupportInteractsWithDataStrictTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (array_column(static::classes(), 0) as $class) {
            $class::shouldBeStrict(false);
        }

        parent::tearDown();
    }

    public static function classes(): array
    {
        $data = ['name' => ['chris'], 'age' => 'abc', 'active' => 'maybe', 'price' => 'abc'];

        return [
            'request' => [Request::class, fn () => Request::create('/search', 'GET', $data)],
            'fluent' => [Fluent::class, fn () => new Fluent($data)],
            'validated input' => [ValidatedInput::class, fn () => new ValidatedInput($data)],
            'uri query string' => [UriQueryString::class, fn () => Uri::of('/search')->withQuery($data)->query()],
            'component attribute bag' => [ComponentAttributeBag::class, fn () => new ComponentAttributeBag($data)],
            'command input' => [CommandInput::class, fn () => new CommandInput([], $data)],
        ];
    }

    #[DataProvider('classes')]
    public function testHelpersAreNotStrictByDefault(string $class, \Closure $make)
    {
        $this->assertFalse($class::isStrict());
        $this->assertSame(0, $make()->integer('age', 5));
    }

    #[DataProvider('classes')]
    public function testShouldBeStrictMakesHelpersFallBackToDefaults(string $class, \Closure $make)
    {
        $class::shouldBeStrict();

        $input = $make();

        $this->assertTrue($class::isStrict());
        $this->assertSame('guest', $input->string('name', 'guest')->value());
        $this->assertSame('guest', $input->str('name', 'guest')->value());
        $this->assertSame(5, $input->integer('age', 5));
        $this->assertSame(1.5, $input->float('price', 1.5));
        $this->assertTrue($input->boolean('active', true));
    }

    #[DataProvider('classes')]
    public function testExplicitArgumentOverridesTheSetting(string $class, \Closure $make)
    {
        $class::shouldBeStrict();

        $this->assertSame(0, $make()->integer('age', 5, false));

        $class::shouldBeStrict(false);

        $this->assertSame(5, $make()->integer('age', 5, true));
    }

    public function testSettingIsScopedToTheClass()
    {
        Request::shouldBeStrict();

        $this->assertTrue(Request::isStrict());
        $this->assertFalse(Fluent::isStrict());
        $this->assertFalse(ValidatedInput::isStrict());
    }
}
