<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Laravel\Prompts\Prompt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class ConfiguresPromptsTest extends TestCase
{
    #[DataProvider('selectDataProvider')]
    public function testSelectFallback($prompt, $answer, $expectedReturn)
    {
        $this->assertSame($expectedReturn, $this->answer($prompt, $answer));
    }

    public static function selectDataProvider()
    {
        return [
            'list with no default' => [fn () => select('Test', ['a', 'b', 'c']), 'b', 'b'],
            'numeric keys with no default' => [fn () => select('Test', [1 => 'a', 2 => 'b', 3 => 'c']), '2', 2],
            'assoc with no default' => [fn () => select('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C']), 'b', 'b'],
            'list with default' => [fn () => select('Test', ['a', 'b', 'c'], 'b'), 'b', 'b'],
            'numeric keys with default' => [fn () => select('Test', [1 => 'a', 2 => 'b', 3 => 'c'], 2), '2', 2],
            'assoc with default' => [fn () => select('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'b'), 'b', 'b'],
        ];
    }

    #[DataProvider('multiselectDataProvider')]
    public function testMultiselectFallback($prompt, $answer, $expectedReturn)
    {
        $this->assertSame($expectedReturn, $this->answer($prompt, $answer));
    }

    public static function multiselectDataProvider()
    {
        return [
            'list with no default' => [fn () => multiselect('Test', ['a', 'b', 'c']), '', []],
            'numeric keys with no default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c']), '', []],
            'assoc with no default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C']), '', []],
            'list with default' => [fn () => multiselect('Test', ['a', 'b', 'c'], ['b', 'c']), 'b,c', ['b', 'c']],
            'numeric keys with default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c'], [2, 3]), '2,3', [2, 3]],
            'assoc with default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], ['b', 'c']), 'b,c', ['b', 'c']],
            'required list with no default' => [fn () => multiselect('Test', ['a', 'b', 'c'], required: true), 'b,c', ['b', 'c']],
            'required numeric keys with no default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c'], required: true), '2,3', [2, 3]],
            'required assoc with no default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], required: true), 'b,c', ['b', 'c']],
            'required list with default' => [fn () => multiselect('Test', ['a', 'b', 'c'], ['b', 'c'], required: true), 'b,c', ['b', 'c']],
            'required numeric keys with default' => [fn () => multiselect('Test', [1 => 'a', 2 => 'b', 3 => 'c'], [2, 3], required: true), '2,3', [2, 3]],
            'required assoc with default' => [fn () => multiselect('Test', ['a' => 'A', 'b' => 'B', 'c' => 'C'], ['b', 'c'], required: true), 'b,c', ['b', 'c']],
        ];
    }

    /**
     * Run the given prompt behind a real command, feeding it the given typed answer
     * through a real input stream, and return what the prompt resolved to.
     */
    protected function answer($prompt, $answer)
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

        $app = new Application(__DIR__);
        $command->setLaravel($app);

        $input = new ArrayInput([]);

        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $answer."\n");
        rewind($stream);
        $input->setStream($stream);

        $command->run($input, new BufferedOutput);

        return $command->answer;
    }
}
