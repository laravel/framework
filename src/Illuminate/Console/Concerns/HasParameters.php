<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Console\Attributes\Argument;
use Illuminate\Console\Attributes\FlagOption;
use Illuminate\Console\Attributes\Option;
use Illuminate\Console\Attributes\OptionalArgument;
use Illuminate\Console\Attributes\RequiredArgument;
use Illuminate\Console\Attributes\ValueOption;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

trait HasParameters
{
    /**
     * Specify the arguments and options on the command.
     *
     * @return void
     */
    protected function specifyParameters()
    {
        // We will loop through all of the arguments and options for the command and
        // set them all on the base command instance. This specifies what can get
        // passed into these commands as "parameters" to control the execution.
        foreach ($this->getArguments() as $arguments) {
            if ($arguments instanceof InputArgument) {
                $this->getDefinition()->addArgument($arguments);
            } else {
                $this->addArgument(...$arguments);
            }
        }

        foreach ($this->getOptions() as $options) {
            if ($options instanceof InputOption) {
                $this->getDefinition()->addOption($options);
            } else {
                $this->addOption(...$options);
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        $classReflection = new ReflectionClass(static::class);
        $attributes = collect($classReflection->getAttributes(Argument::class, ReflectionAttribute::IS_INSTANCEOF));

        if ($attributes->isEmpty()) {
            return [];
        }

        $generalArguments = $this->buildArgumentsList($attributes, Argument::class);

        $requiredArguments = $this->buildArgumentsList($attributes, RequiredArgument::class)
            ->merge($generalArguments->where('mode', InputArgument::REQUIRED));

        [$arrayArguments, $nonArrayArguments] = $requiredArguments->partition(
            fn (array $argument) => $argument['mode'] > InputArgument::REQUIRED
        );

        $optionalArguments = $this->buildArgumentsList($attributes, OptionalArgument::class)
            ->merge($generalArguments->filter(
                fn (array $argument) => $argument['mode'] === InputArgument::OPTIONAL || $argument['mode'] === InputArgument::IS_ARRAY)
            );

        return [
            ...$nonArrayArguments,
            ...$optionalArguments,
            ...$arrayArguments,
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $classReflection = new ReflectionClass(static::class);
        $attributes = collect($classReflection->getAttributes(Option::class, ReflectionAttribute::IS_INSTANCEOF));

        if ($attributes->isEmpty()) {
            return [];
        }

        $valueOptions = $this->buildOptionsList($attributes, ValueOption::class);
        $flagOptions = $this->buildOptionsList($attributes, FlagOption::class);

        return [
            ...$valueOptions,
            ...$flagOptions,
        ];
    }

    private function buildArgumentsList(Collection $attributes, string $attributeClass): Collection
    {
        return $attributes->filter(fn (ReflectionAttribute $attribute) => $attribute->getName() === $attributeClass)
            ->map(function (ReflectionAttribute $argumentAttribute) {
                /** @var Argument $attribute */
                $attribute = $argumentAttribute->newInstance();

                return [
                    'name' => $attribute->name,
                    'mode' => $this->resolveArgumentMode($attribute),
                    'description' => $attribute->description,
                    'default' => $attribute->default,
                    'suggestedValues' => $attribute->suggestedValues,
                ];
            });
    }

    private function resolveArgumentMode(Argument $attribute): int
    {
        return match (true) {
            $attribute->required && $attribute->array => InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            $attribute->required && ! $attribute->array => InputArgument::REQUIRED,
            ! $attribute->required && $attribute->array => InputArgument::IS_ARRAY,
            ! $attribute->required && ! $attribute->array => InputArgument::OPTIONAL,
            default => InputArgument::REQUIRED,
        };
    }

    private function buildOptionsList(Collection $attributes, string $attributeClass): Collection
    {
        return $attributes->filter(fn (ReflectionAttribute $attribute) => $attribute->getName() === $attributeClass)
            ->map(function (ReflectionAttribute $optionAttribute) {
                /** @var Option $attribute */
                $attribute = $optionAttribute->newInstance();

                return [
                    'name' => $attribute->name,
                    'shortcut' => $attribute->shortcut,
                    'mode' => $attribute->mode,
                    'description' => $attribute->description,
                    'default' => $attribute->default,
                    'suggestedValues' => $attribute->suggestedValues,
                ];
            });
    }
}
