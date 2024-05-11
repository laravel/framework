<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

#[AsCommand(name: 'find')]
class FindCommand extends Command
{
    use Conditionable;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'find {--deep : Search in the arguments and options descriptions too}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search console command';

    /**
     * The array of available commands.
     *
     * @var array
     */
    protected array $commands;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $description = new ApplicationDescription($this->getApplication());
        $this->commands = Arr::where($description->getCommands(), fn (SymfonyCommand $command) => ! $command instanceof $this);

        $this->searchCommand();
    }

    /**
     * Search for a command.
     *
     * @return void
     */
    protected function searchCommand(): void
    {
        $command = search(
            label: 'Search for command',
            options: fn (string $value) => $this->search($value),
            required: true
        );

        (new DescriptorHelper())->describe($this->output, $this->commands[$command]);

        $action = select(
            label: 'Choose a action',
            options: [
                'Execute the command',
                'Search for another command',
                'Exit',
            ],
        );

        if ($action == 'Execute the command') {
            $this->executeFoundCommand($this->commands[$command]);

            return;
        }

        if ($action == 'Search for another command') {
            $this->searchCommand();
        }
    }

    /**
     * Execute the found command.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     */
    protected function executeFoundCommand(SymfonyCommand $command): void
    {
        note(sprintf('Execute the „%s“ command', $command->getName()));
        $arguments = [];
        $definition = $command->getDefinition();
        $array = array_merge($definition->getArguments(), $definition->getOptions());

        foreach ($array as $key => $input) {
            if (in_array($key, ['help', 'quiet', 'ansi', 'version', 'no-interaction', 'verbose'])) {
                continue;
            }

            if ($input instanceof InputOption) {
                $key = '--'.$key;

                if (! $input->acceptValue()) {
                    $arguments[$key] = confirm(
                        label: $input->getName(),
                        default: (bool) $input->getDefault(),
                        required: false,
                        hint: $input->getDescription(),
                    );

                    continue;
                }
            }

            /* @var \Symfony\Component\Console\Input\InputArgument|\Symfony\Component\Console\Input\InputOption $input */
            if ($input->isArray()) {
                $arguments[$key] = textarea(
                    label: $input->getName(),
                    placeholder: 'one value in each line',
                    default: implode(PHP_EOL, (array) $input->getDefault()),
                    required: $input instanceof InputArgument && $input->isRequired(),
                    hint: $input->getDescription(),
                );
                $arguments[$key] = array_filter(
                    array_map('trim', explode(PHP_EOL, $arguments[$key]))
                );

                continue;
            }

            $arguments[$key] = text(
                label: $input->getName(),
                default: (string) $input->getDefault(),
                required: $input instanceof InputArgument && $input->isRequired(),
                hint: $input->getDescription(),
            );
        }

        $arguments = array_filter($arguments, fn ($argument) => $argument !== '');

        $this->call($command->getName(), $arguments);
    }

    /**
     * Search for command and return the result prioritized.
     *
     * @param  string  $value
     * @return array
     */
    protected function search(string $value): array
    {
        if (empty(trim($value))) {
            return [];
        }

        $value = preg_split('/\s+/', $value, flags: PREG_SPLIT_NO_EMPTY);

        $result = array_merge(
            Arr::where($this->commands, fn (SymfonyCommand $command) => $command->getName() == $value),
            Arr::where($this->commands, fn (SymfonyCommand $command) => Str::containsAll($command->getName(), $value, true)),
            Arr::where($this->commands, fn (SymfonyCommand $command) => Str::containsAll($command->getDescription(), $value, true)),
            $this->when(
                $this->option('deep'),
                function () use ($value) {
                    return Arr::where($this->commands, function (SymfonyCommand $command) use ($value) {
                        $definition = $command->getDefinition();
                        $deep = implode(PHP_EOL, array_merge(
                            Arr::map($definition->getArguments(), fn (InputArgument $argument) => $argument->getDescription()),
                            Arr::map($definition->getOptions(), fn (InputOption $option) => $option->getDescription()),
                        ));

                        return Str::containsAll($deep, $value, true);
                    });
                },
                fn () => []
            ),
        );

        $result = Arr::mapWithKeys($result, function (SymfonyCommand $command) {
            $label = windows_os() ? $command->getDescription() : sprintf('[%s] %s', $command->getName(), $command->getDescription());

            return [$command->getName() => $label];
        });

        if (windows_os()) {
            $result[$this->getName()] = $this->getDescription();
        }

        return $result;
    }
}
