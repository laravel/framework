<?php

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\TextPrompt;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FakeCommandWithInputPrompting extends Command implements PromptsForMissingInput
{
    protected $signature = 'fake-command-for-testing {name : An argument}';

    public $prompted = false;

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        Prompt::fallbackWhen(true);
        TextPrompt::fallbackUsing(function () {
            $this->prompted = true;

            return 'foo';
        });

        parent::interact($input, $output);
    }

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
