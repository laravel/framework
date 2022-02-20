<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Console\Reflections\ArgumentReflection;
use Illuminate\Console\Reflections\OptionReflection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

trait HasAttributeSyntax
{

    /**
     * Configure the console command using an Attribute definition.
     *
     * @return void
     */
    protected function configureUsingAttributeDefinition()
    {
        $this->configureArgumentsUsingAttributeDefinition();
        $this->configureOptionsUsingAttributeDefinition();
    }

    protected function configureArgumentsUsingAttributeDefinition(): void
    {
        $this->reflection
            ->getArguments()
            ->each(function (ArgumentReflection $argumentReflection) {
                $this->getDefinition()
                    ->addArgument(
                        $this->propertyToArgument($argumentReflection)
                    );
            });
    }

    protected function configureOptionsUsingAttributeDefinition(): void
    {
        $this->reflection
            ->getOptions()
            ->each(function (OptionReflection $optionReflection) {
                $this->getDefinition()
                    ->addOption($this->propertyToOption($optionReflection));
            });
    }

    protected function hydrateArguments(): void
    {
        $this->reflection
            ->getArguments()
            ->each(function (ArgumentReflection $argumentReflection) {
                $this->{$argumentReflection->getName()} = $argumentReflection->castTo($this->argument($argumentReflection->getAlias() ?? $argumentReflection->getName()));
            });
    }

    protected function hydrateOptions(): void
    {
        $this->reflection
            ->getOptions()
            ->each(function (OptionReflection $optionReflection) {
                $consoleName = $optionReflection->getAlias() ?? $optionReflection->getName();

                if (!$optionReflection->hasRequiredValue()) {
                    $this->{$optionReflection->getName()} = $optionReflection->castTo($this->option($consoleName));
                    return;
                }

                if ($this->option($consoleName) === null) {
                    return;
                }

                $this->{$optionReflection->getName()} = $optionReflection->castTo($this->option($consoleName));

            });
    }


    protected function propertyToArgument(ArgumentReflection $argument): InputArgument
    {
        return match (true) {
            $argument->isArray() && !$argument->isOptional() => $this->makeInputArgument($argument,
                InputArgument::IS_ARRAY | InputArgument::REQUIRED),

            $argument->isArray() => $this->makeInputArgument($argument, InputArgument::IS_ARRAY,
                $argument->getDefaultValue()),

            $argument->isOptional() || $argument->getDefaultValue() => $this->makeInputArgument($argument,
                InputArgument::OPTIONAL, $argument->getDefaultValue()),

            default => $this->makeInputArgument($argument, InputArgument::REQUIRED),
        };
    }

    protected function propertyToOption(OptionReflection $option): InputOption
    {
        return match (true) {
            $option->hasValue() && $option->isArray() => $this->makeInputOption(
                $option,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $option->getDefaultValue()
            ),

            $option->hasValue() && !$option->isOptional() => $this->makeInputOption($option,
                InputOption::VALUE_REQUIRED),

            $option->hasValue() => $this->makeInputOption($option, InputOption::VALUE_OPTIONAL,
                $option->getDefaultValue()),

            $option->isNegatable() => $this->makeInputOption(
                $option,
                InputOption::VALUE_NEGATABLE,
                $option->getDefaultValue() !== null ? $option->getDefaultValue() : false
            ),

            default => $this->makeInputOption($option, InputOption::VALUE_NONE),
        };
    }

    protected function makeInputArgument(
        ArgumentReflection $argument,
        int $mode,
        string|bool|int|float|array|null $default = null
    ): InputArgument {
        return new InputArgument(
            $argument->getAlias() ?? $argument->getName(),
            $mode,
            $argument->getDescription(),
            $default
        );
    }

    protected function makeInputOption(
        OptionReflection $option,
        int $mode,
        string|bool|int|float|array|null $default = null
    ): InputOption {
        return new InputOption(
            $option->getAlias() ?? $option->getName(),
            $option->getShortcut(),
            $mode,
            $option->getDescription(),
            $default
        );
    }
}
