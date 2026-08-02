<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Concerns\PromptsForMissingInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class PromptsForMissingInputTest extends TestCase
{
    public function testDidReceiveOptionsReturnsFalseWhenAllOptionsMatchTheirDefaults(): void
    {
        $stub = $this->makeStub();

        $input = new ArrayInput([], $stub->getDefinition());

        $this->assertFalse($stub->didReceiveOptions($input));
    }

    public function testDidReceiveOptionsReturnsTrueWhenAnOptionDiffersFromItsDefault(): void
    {
        $stub = $this->makeStub();

        $input = new ArrayInput(['--force' => true], $stub->getDefinition());

        $this->assertTrue($stub->didReceiveOptions($input));
    }

    protected function makeStub()
    {
        return new class
        {
            use PromptsForMissingInput {
                didReceiveOptions as public;
            }

            public function getDefinition(): InputDefinition
            {
                return new InputDefinition([
                    new InputOption('force', null, InputOption::VALUE_NONE),
                    new InputOption('name', null, InputOption::VALUE_OPTIONAL, '', 'default-name'),
                ]);
            }
        };
    }
}
