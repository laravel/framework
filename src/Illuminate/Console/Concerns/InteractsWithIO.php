<?php

namespace Illuminate\Console\Concerns;

use Closure;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

trait InteractsWithIO
{
    /**
     * The console components factory.
     *
     * @var \Illuminate\Console\View\Components\Factory
     */
    protected $components;

    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * The default verbosity of output commands.
     *
     * @var int
     */
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
     *
     * @var array
     */
    protected $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    /**
     * Determine if the given argument is present.
     *
     * @param  string|int  $name
     * @return bool
     */
    public function hasArgument($name)
    {
        return $this->input->hasArgument($name);
    }

    /**
     * Get the value of a command argument.
     *
     * @param  string|null  $key
     * @return array|string|bool|null
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get all of the arguments passed to the command.
     *
     * @return array
     */
    public function arguments()
    {
        return $this->argument();
    }

    /**
     * Determine whether the option is defined in the command signature.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasOption($name)
    {
        return $this->input->hasOption($name);
    }

    /**
     * Get the value of a command option.
     *
     * @param  string|null  $key
     * @return string|array|bool|null
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    /**
     * Get all of the options passed to the command.
     *
     * @return array
     */
    public function options()
    {
        return $this->option();
    }

    /**
     * Confirm a question with the user.
     *
     * @param  string  $question
     * @param  bool  $default
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * @param  string  $question
     * @param  string|null  $default
     * @return mixed
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string  $question
     * @param  array|callable  $choices
     * @param  string|null  $default
     * @return mixed
     */
    public function anticipate($question, $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string  $question
     * @param  array|callable  $choices
     * @param  string|null  $default
     * @return mixed
     */
    public function askWithCompletion($question, $choices, $default = null)
    {
        $question = new Question($question, $default);

        is_callable($choices)
            ? $question->setAutocompleterCallback($choices)
            : $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param  string  $question
     * @param  bool  $fallback
     * @return mixed
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);

        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param  string  $question
     * @param  array  $choices
     * @param  string|int|null  $default
     * @param  mixed  $attempts
     * @param  bool  $multiple
     * @return string|array
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = false)
    {
        $question = new ChoiceQuestion($question, $choices, $default);

        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
     *
     * @param  array  $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @param  \Symfony\Component\Console\Helper\TableStyle|string  $tableStyle
     * @param  array  $columnStyles
     * @return void
     */
    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        $table = new Table($this->output);

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        $table->render();
    }

    /**
     * Execute a given callback while advancing a progress bar.
     *
     * @param  iterable|int  $totalSteps
     * @param  \Closure  $callback
     * @return mixed|void
     */
    public function withProgressBar($totalSteps, Closure $callback)
    {
        $bar = $this->output->createProgressBar(
            is_iterable($totalSteps) ? count($totalSteps) : $totalSteps
        );

        $bar->start();

        if (is_iterable($totalSteps)) {
            foreach ($totalSteps as $key => $value) {
                $callback($value, $bar, $key);

                $bar->advance();
            }
        } else {
            $callback($bar);
        }

        $bar->finish();

        if (is_iterable($totalSteps)) {
            return $totalSteps;
        }
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        if (! $this->output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->output->getFormatter()->setStyle('warning', $style);
        }

        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Write a string in an alert box.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function alert($string, $verbosity = null)
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->comment(str_repeat('*', $length), $verbosity);
        $this->comment('*     '.$string.'     *', $verbosity);
        $this->comment(str_repeat('*', $length), $verbosity);

        $this->comment('', $verbosity);
    }

    /**
     * Write a blank line.
     *
     * @param  int  $count
     * @return $this
     */
    public function newLine($count = 1)
    {
        $this->output->newLine($count);

        return $this;
    }

    /**
     * Set the input interface implementation.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return void
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Set the output interface implementation.
     *
     * @param  \Illuminate\Console\OutputStyle  $output
     * @return void
     */
    public function setOutput(OutputStyle $output)
    {
        $this->output = $output;
    }

    /**
     * Set the verbosity level.
     *
     * @param  string|int  $level
     * @return void
     */
    protected function setVerbosity($level)
    {
        $this->verbosity = $this->parseVerbosity($level);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param  string|int|null  $level
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        $level ??= '';

        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        } elseif (! is_int($level)) {
            $level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Get the output implementation.
     *
     * @return \Illuminate\Console\OutputStyle
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Get the output component factory implementation.
     *
     * @return \Illuminate\Console\View\Components\Factory
     */
    public function outputComponents()
    {
        return $this->components;
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function string(string $key, ?string $default = null): string
    {
        $value = match (true) {
            $this->hasArgument($key) => $this->getArgument($key),
            $this->hasOption($key) => $this->getOption($key),
            default => $default ?? throw new InvalidArgumentException(sprintf('"%s" is neither an option nor an argument of this command', $key)),
        };

        if (! is_string($value)) {
            throw new TypeError(sprintf('"%s" is not of type string. %s given', $key, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function integer(string $key, ?int $default = null): int
    {
        $value = match (true) {
            $this->hasArgument($key) => $this->getArgument($key),
            $this->hasOption($key) => $this->getOption($key),
            default => $default ?? throw new InvalidArgumentException(sprintf('"%s" is neither an option nor an argument of this command', $key)),
        };

        if (! is_int($value)) {
            throw new TypeError(sprintf('"%s" is not of type integer. %s given', $key, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function int(string $key, $default): int
    {
        return $this->integer($key, $default);
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function float(string $key, ?float $default = null): float
    {
        $value = match (true) {
            $this->hasArgument($key) => $this->getArgument($key),
            $this->hasOption($key) => $this->getOption($key),
            default => $default ?? throw new InvalidArgumentException(sprintf('"%s" is neither an option nor an argument of this command', $key)),
        };

        if (! is_float($value)) {
            throw new TypeError(sprintf('"%s" is not of type float. %s given', $key, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function double(string $key, $default): float
    {
        return $this->float($key, $default);
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function number(string $key, int|float|null $default = null): int|float
    {
        $value = match (true) {
            $this->hasArgument($key) => $this->getArgument($key),
            $this->hasOption($key) => $this->getOption($key),
            default => $default ?? throw new InvalidArgumentException(sprintf('"%s" is neither an option nor an argument of this command', $key)),
        };

        if (! is_int($value) && ! is_float($value)) {
            throw new TypeError(sprintf('"%s" is neither of type float nor integer. %s given', $key, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function boolean(string $key, int|float|null $default = null): bool
    {
        $value = match (true) {
            $this->hasArgument($key) => $this->getArgument($key),
            $this->hasOption($key) => $this->getOption($key),
            default => $default ?? throw new InvalidArgumentException(sprintf('"%s" is neither an option nor an argument of this command', $key)),
        };

        if (! is_bool($value)) {
            throw new TypeError(sprintf('"%s" is not of type boolean. %s given', $key, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function bool(string $key, $default): bool
    {
        return $this->boolean($key, $default);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     * @throws \TypeError on type mismatch
     */
    public function array(string $key, ?array $default = null): array
    {
        $value = match (true) {
            $this->hasArgument($key) => $this->getArgument($key),
            $this->hasOption($key) => $this->getOption($key),
            default => $default ?? throw new InvalidArgumentException(sprintf('"%s" is neither an option nor an argument of this command', $key)),
        };

        if (! is_array($value)) {
            throw new TypeError(sprintf('"%s" is not of type array. %s given', $key, get_debug_type($value)));
        }

        return $value;
    }
}
