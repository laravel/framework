<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:command')]
class ConsoleMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Artisan command';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Console command';

    /**
     * Interactive command configuration.
     *
     * @var array
     */
    protected $interactiveConfig = [];

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $command = $this->option('command') ?: 'app:'.(new Stringable($name))->classBasename()->kebab()->value();

        // Build full signature with arguments and options if interactive mode was used
        if (! empty($this->interactiveConfig)) {
            $signature = $this->buildSignature($command);
            $description = $this->interactiveConfig['description'] ?? 'Command description';

            $stub = str_replace(['dummy:command', '{{ command }}'], $signature, $stub);
            $stub = str_replace('Command description', $description, $stub);
        } else {
            $stub = str_replace(['dummy:command', '{{ command }}'], $command, $stub);
        }

        return $stub;
    }

    /**
     * Build the full command signature with arguments and options.
     *
     * @param  string  $baseSignature
     * @return string
     */
    protected function buildSignature($baseSignature)
    {
        $signature = $baseSignature;

        // Add arguments
        foreach ($this->interactiveConfig['arguments'] ?? [] as $argument) {
            $arg = $argument['name'];

            if ($argument['mode'] === 'optional') {
                $arg = "{$arg}?";
            } elseif ($argument['mode'] === 'array') {
                $arg = "{$arg}?*";
            }

            $arg .= ' : '.$argument['description'];
            $signature .= " {{$arg}}";
        }

        // Add options
        foreach ($this->interactiveConfig['options'] ?? [] as $option) {
            $opt = '--'.$option['name'];

            if (isset($option['shortcut']) && $option['shortcut']) {
                $opt = '-'.strtoupper($option['shortcut']).'|'.$opt;
            }

            if ($option['mode'] === 'optional') {
                $opt .= '=';
            } elseif ($option['mode'] === 'required') {
                $opt .= '=';
            } elseif ($option['mode'] === 'array') {
                $opt .= '=*';
            }

            $opt .= ' : '.$option['description'];

            if (isset($option['default']) && $option['mode'] === 'optional') {
                // Escape single quotes in default value
                $defaultValue = str_replace("'", "\\'", $option['default']);
                $signature .= " {{$opt}} {{--{$option['name']}={$defaultValue}}}";
            } else {
                $signature .= " {{$opt}}";
            }
        }

        return $signature;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/console.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Console\Commands';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the console command already exists'],
            ['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that will be used to invoke the class'],
            ['interactive', 'i', InputOption::VALUE_NONE, 'Interactively build the command signature with arguments and options'],
        ];
    }

    /**
     * Interact with the user before validating the input.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        if ($this->option('interactive') && ! $this->isReservedName($this->getNameInput())) {
            $this->interactivelyBuildCommand($input);
        }
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput())) {
            return;
        }

        // Only proceed with interactive mode if explicitly requested
        if (! $this->option('interactive')) {
            return;
        }

        $this->interactivelyBuildCommand($input);
    }

    /**
     * Interactively build the command configuration.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return void
     */
    protected function interactivelyBuildCommand(InputInterface $input)
    {
        // Get command signature
        $signature = text(
            label: 'What is the command signature?',
            placeholder: 'e.g., app:send-report',
            required: true,
            validate: fn ($value) => match (true) {
                empty($value) => 'Command signature is required.',
                str_contains($value, ' ') => 'Signature should not contain spaces. Use arguments/options instead.',
                ! preg_match('/^[a-z0-9:_-]+$/i', $value) => 'Signature can only contain letters, numbers, colons, hyphens, and underscores.',
                default => null,
            }
        );

        $input->setOption('command', $signature);

        // Get command description
        $description = text(
            label: 'What is the command description?',
            placeholder: 'e.g., Send daily report to administrators',
            required: true,
            validate: fn ($value) => empty($value) ? 'Description is required.' : null
        );

        $this->interactiveConfig['description'] = $description;
        $this->interactiveConfig['arguments'] = [];
        $this->interactiveConfig['options'] = [];

        // Build arguments
        while (confirm('Would you like to add an argument?', default: false)) {
            $this->interactiveConfig['arguments'][] = $this->promptForArgument();
        }

        // Build options
        while (confirm('Would you like to add an option?', default: false)) {
            $this->interactiveConfig['options'][] = $this->promptForOption();
        }
    }

    /**
     * Prompt for argument details.
     *
     * @return array
     */
    protected function promptForArgument()
    {
        $name = text(
            label: 'Argument name?',
            placeholder: 'e.g., user',
            required: true,
            validate: fn ($value) => match (true) {
                empty($value) => 'Argument name is required.',
                ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value) => 'Argument name must start with a letter or underscore and contain only letters, numbers, and underscores.',
                default => null,
            }
        );

        $description = text(
            label: 'Argument description?',
            placeholder: 'e.g., The user ID to process',
            required: true,
            validate: fn ($value) => empty($value) ? 'Description is required.' : null
        );

        $modeMap = [
            'Required' => 'required',
            'Optional' => 'optional',
            'Optional array (multiple values)' => 'array',
        ];

        $selectedMode = select(
            label: 'Is this argument required or optional?',
            options: array_keys($modeMap),
            default: 'Required'
        );

        $mode = $modeMap[$selectedMode];

        return compact('name', 'description', 'mode');
    }

    /**
     * Prompt for option details.
     *
     * @return array
     */
    protected function promptForOption()
    {
        $name = text(
            label: 'Option name?',
            placeholder: 'e.g., queue',
            required: true,
            validate: fn ($value) => match (true) {
                empty($value) => 'Option name is required.',
                ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $value) => 'Option name must start with a letter or underscore and contain only letters, numbers, hyphens, and underscores.',
                default => null,
            }
        );

        $shortcut = text(
            label: 'Option shortcut? (Optional, single letter)',
            placeholder: 'e.g., Q',
            required: false,
            validate: fn ($value) => ($value && ! preg_match('/^[a-zA-Z]$/', $value))
                ? 'Shortcut must be a single letter.'
                : null
        );

        $description = text(
            label: 'Option description?',
            placeholder: 'e.g., Queue the job execution',
            required: true,
            validate: fn ($value) => empty($value) ? 'Description is required.' : null
        );

        $modeMap = [
            'Flag (no value)' => 'none',
            'Optional value' => 'optional',
            'Required value' => 'required',
            'Array (multiple values)' => 'array',
        ];

        $selectedMode = select(
            label: 'What type of option is this?',
            options: array_keys($modeMap),
            default: 'Flag (no value)'
        );

        $mode = $modeMap[$selectedMode];
        $default = null;

        if (in_array($mode, ['optional', 'required'])) {
            $defaultValue = text(
                label: 'Default value? (Optional)',
                placeholder: 'Leave empty for no default',
                required: false
            );

            if ($defaultValue !== '' && $defaultValue !== null) {
                $default = $defaultValue;
            }
        }

        return array_filter(compact('name', 'shortcut', 'description', 'mode', 'default'), fn ($v) => $v !== null && $v !== '');
    }
}
