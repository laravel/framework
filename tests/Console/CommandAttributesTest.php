<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Attributes\FlagOption;
use Illuminate\Console\Attributes\OptionalArgument;
use Illuminate\Console\Attributes\RequiredArgument;
use Illuminate\Console\Attributes\ValueOption;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandAttributesTest extends TestCase
{
    public function testCommandAttributesForArgumentsAndOptions()
    {
        $command = resolve(TestCommand::class);
        $definition = $command->getDefinition();
        $arguments = Collection::make($definition->getArguments())
            ->keyBy(fn (InputArgument $argument) => $argument->getName()
        );
        $options = Collection::make($definition->getOptions())
            ->keyBy(fn (InputOption $option) => $option->getName()
        );

        $this->assertCount(2, $arguments);
        $this->assertTrue($arguments->get('name')->isRequired());
        $this->assertFalse($arguments->get('age')->isRequired());
        $this->assertEquals(18, $arguments->get('age')->getDefault());

        $this->assertCount(3, $options);
        $this->assertEquals('m', $options->get('negative')->getShortcut());
        $this->assertTrue($options->get('negative')->isNegatable());
        $this->assertEquals('The year', $options->get('year')->getDescription());
        $this->assertTrue($options->get('scores')->isArray());
        $this->assertEquals([1, 2, 3], $options->get('scores')->getDefault());
    }
}

#[RequiredArgument(name: 'name')]
#[OptionalArgument(name: 'age', default: 18)]
#[FlagOption(name: 'negative', shortcut: 'm', negatable: true)]
#[ValueOption(name: 'year', description: 'The year')]
#[ValueOption(name: 'scores', array: true, default: [1, 2, 3])]
class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
