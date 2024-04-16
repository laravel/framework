<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Laravel\Prompts\Prompt;
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
    public function testSelectFallback($prompt, $expectedOptions, $expectedDefault, $return, $expectedReturn)
    {
        Prompt::fallbackWhen(true);

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
            ->with('Test', $expectedOptions, $expectedDefault)
            ->andReturn($return)
        );

        $this->assertSame($expectedReturn, $command->answer);
    }

    public static function selectDataProvider()
    {
        return [
            'list with no default' => [fn () => select('Test', ['a', 'b', 'c']), ['a', 'b', 'c'], null, 'b', 'b'],
            'numeric keys with no default' => [fn () => select('Test', [1 => 'a', 2 => 'b', 3 => 'c']), [1 => 'a', 2 => 'b', 3 => 'c'], null, '2', 2],
            'assoc with no default' => [fn () => select('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C']), ['a' => 'A', 'b' => 'B', 'c' => 'C'], null, 'b', 'b'],
            'list with default' => [fn () => select('Test', ['a', 'b', 'c'], 'b'), ['a', 'b', 'c'], 'b', 'b', 'b'],
            'numeric keys with default' => [fn () => select('Test', [1 => 'a', 2 => 'b', 3 => 'c'], 2), [1 => 'a', 2 => 'b', 3 => 'c'], 2, '2', 2],
            'assoc with default' => [fn () => select('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'b'), ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'b', 'b', 'b'],
        ];
    }

    #[DataProvider('multiselectDataProvider')]
    public function testMultiselectFallback($prompt, $expectedOptions, $expectedDefault, $return, $expectedReturn)
    {
        Prompt::fallbackWhen(true);

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
            ->with('Test', $expectedOptions, $expectedDefault, null, true)
            ->andReturn($return)
        );

        $this->assertSame($expectedReturn, $command->answer);
    }

    public static function multiselectDataProvider()
    {
        return [
            'list with no default' => [fn () => multiselect('Test', ['a', 'b', 'c']), ['None', 'a', 'b', 'c'], 'None', ['None'], []],
            'numeric keys with no default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c']), ['' => 'None', 1 => 'a', 2 => 'b', 3 => 'c'], 'None', [''], []],
            'assoc with no default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C']), ['' => 'None', 'a' => 'A', 'b' => 'B', 'c' => 'C'], 'None', [''], []],
            'list with default' => [fn () => multiselect('Test', ['a', 'b', 'c'], ['b', 'c']), ['None', 'a', 'b', 'c'], 'b,c', ['b', 'c'], ['b', 'c']],
            'numeric keys with default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c'], [2, 3]), ['' => 'None', 1 => 'a', 2 => 'b', 3 => 'c'], '2,3', ['2', '3'], [2, 3]],
            'assoc with default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], ['b', 'c']), ['' => 'None', 'a' => 'A', 'b' => 'B', 'c' => 'C'], 'b,c', ['b', 'c'], ['b', 'c']],
            'required list with no default' => [fn () => multiselect('Test', ['a', 'b', 'c'], required: true), ['a', 'b', 'c'], null, ['b', 'c'], ['b', 'c']],
            'required numeric keys with no default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c'], required: true), [1 => 'a', 2 => 'b', 3 => 'c'], null, ['2', '3'], [2, 3]],
            'required assoc with no default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], required: true), ['a' => 'A', 'b' => 'B', 'c' => 'C'], null, ['b', 'c'], ['b', 'c']],
            'required list with default' => [fn () => multiselect('Test', ['a', 'b', 'c'], ['b', 'c'], required: true), ['a', 'b', 'c'], 'b,c', ['b', 'c'], ['b', 'c']],
            'required numeric keys with default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c'], [2, 3], required: true), [1 => 'a', 2 => 'b', 3 => 'c'], '2,3', ['2', '3'], [2, 3]],
            'required assoc with default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], ['b', 'c'], required: true), ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'b,c', ['b', 'c'], ['b', 'c']],
        ];
    }

    protected function runCommand($command, $expectations)
    {
        $command->setLaravel($application = m::mock(Application::class));

        $application->shouldReceive('make')->withArgs(fn ($abstract) => $abstract === OutputStyle::class)->andReturn($outputStyle = m::mock(OutputStyle::class));
        $application->shouldReceive('make')->withArgs(fn ($abstract) => $abstract === Factory::class)->andReturn($factory = m::mock(Factory::class));
        $application->shouldReceive('runningUnitTests')->andReturn(false);
        $application->shouldReceive('call')->with([$command, 'handle'])->andReturnUsing(fn ($callback) => call_user_func($callback));
        $outputStyle->shouldReceive('newLinesWritten')->andReturn(1);

        $expectations($factory);

        $command->run(new ArrayInput([]), new NullOutput);
    }
}
