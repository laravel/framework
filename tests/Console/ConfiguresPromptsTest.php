<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class ConfiguresPromptsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    #[DataProvider('selectDataProvider')]
    public function testSelectFallback($prompt, $expectedDefault, $selection, $expectedReturn)
    {
        $command = new class($prompt) extends Command
        {
            public $answer;

            public function __construct(protected $prompt)
            {
                parent::__construct();
            }

            public function handle()
            {
                $this->answer = ($this->prompt)();
            }
        };

        $this->runCommand($command, fn ($components) => $components
            ->expects('choice')
            ->withArgs(fn ($question, $options, $default) => $default === $expectedDefault)
            ->andReturnUsing(fn ($question, $options, $default) => $options[$selection])
        );

        $this->assertSame($expectedReturn, $command->answer);
    }

    public static function selectDataProvider()
    {
        return [
            'list with no default' => [fn () => select('foo', ['a', 'b', 'c']), null, 1, 'b'],
            'numeric keys with no default' => [fn () => select('foo', [1 => 'a', 2 => 'b', 3 => 'c']), null, 1, 2],
            'assoc with no default' => [fn () => select('foo', ['a' => 'A', 'b' => 'B', 'c' => 'C']), null, 1, 'b'],
            'list with default' => [fn () => select('foo', ['a', 'b', 'c'], 'b'), 1, 1, 'b'],
            'numeric keys with default' => [fn () => select('foo', [1 => 'a', 2 => 'b', 3 => 'c'], 2), 1, 1, 2],
            'assoc with default' => [fn () => select('foo', ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'b'), 1, 1, 'b'],
        ];
    }

    #[DataProvider('multiselectDataProvider')]
    public function testMultiselectFallback($prompt, $expectedDefault, $selection, $expectedReturn)
    {
        $command = new class($prompt) extends Command
        {
            public $answer;

            public function __construct(protected $prompt)
            {
                parent::__construct();
            }

            public function handle()
            {
                $this->answer = ($this->prompt)();
            }
        };

        $this->runCommand($command, fn ($components) => $components
            ->expects('choice')
            ->withArgs(fn ($question, $options, $default, $multiple) => $default === $expectedDefault && $multiple === true)
            ->andReturnUsing(fn ($question, $options, $default, $multiple) => array_values(array_filter($options, fn ($index) => in_array($index, $selection), ARRAY_FILTER_USE_KEY)))
        );

        $this->assertSame($expectedReturn, $command->answer);
    }

    public static function multiselectDataProvider()
    {
        return [
            'list with no default' => [fn () => multiselect('foo', ['a', 'b', 'c']), '0', [2, 3], ['b', 'c']],
            'numeric keys with no default' => [fn () => multiselect('foo', [1 => 'a', 2 => 'b', 3 => 'c']), '0', [2, 3], [2, 3]],
            'assoc with no default' => [fn () => multiselect('foo', ['a' => 'A', 'b' => 'B', 'c' => 'C']), '0', [2, 3], ['b', 'c']],
            'list with default' => [fn () => multiselect('foo', ['a', 'b', 'c'], ['b', 'c']), '2,3', [2, 3], ['b', 'c']],
            'numeric keys with default' => [fn () => multiselect('foo', [1 => 'a', 2 => 'b', 3 => 'c'], [2, 3]), '2,3', [2, 3], [2, 3]],
            'assoc with default' => [fn () => multiselect('foo', ['a' => 'A', 'b' => 'B', 'c' => 'C'], ['b', 'c']), '2,3', [2, 3], ['b', 'c']],
            'required list with no default' => [fn () => multiselect('foo', ['a', 'b', 'c'], required: true), null, [1, 2], ['b', 'c']],
            'required numeric keys with no default' => [fn () => multiselect('foo', [1 => 'a', 2 => 'b', 3 => 'c'], required: true), null, [1, 2], [2, 3]],
            'required assoc with no default' => [fn () => multiselect('foo', ['a' => 'A', 'b' => 'B', 'c' => 'C'], required: true), null, [1, 2], ['b', 'c']],
            'required list with default' => [fn () => multiselect('foo', ['a', 'b', 'c'], ['b', 'c'], required: true), '1,2', [1, 2], ['b', 'c']],
            'required numeric keys with default' => [fn () => multiselect('foo', [1 => 'a', 2 => 'b', 3 => 'c'], [2, 3], required: true), '1,2', [1, 2], [2, 3]],
            'required assoc with default' => [fn () => multiselect('foo', ['a' => 'A', 'b' => 'B', 'c' => 'C'], ['b', 'c'], required: true), '1,2', [1, 2], ['b', 'c']],
        ];
    }

    protected function runCommand($command, $expectations)
    {
        $command->setLaravel($application = m::mock(Application::class));

        $application->shouldReceive('make')->withArgs(fn ($abstract) => $abstract === OutputStyle::class)->andReturn($outputStyle = m::mock(OutputStyle::class));
        $application->shouldReceive('make')->withArgs(fn ($abstract) => $abstract === Factory::class)->andReturn($factory = m::mock(Factory::class));
        $application->shouldReceive('runningUnitTests')->andReturn(true);
        $application->shouldReceive('call')->with([$command, 'handle'])->andReturnUsing(fn ($callback) => call_user_func($callback));
        $outputStyle->shouldReceive('newLinesWritten')->andReturn(1);

        $expectations($factory);

        $command->run(new ArrayInput([]), new NullOutput);
    }
}
