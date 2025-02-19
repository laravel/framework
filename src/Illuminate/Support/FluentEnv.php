<?php

namespace Illuminate\Support;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use RuntimeException;

class FluentEnv
{
    protected array $keys = [];
    protected mixed $default = null;
    protected string|array $validationRules = '';
    protected array $validationMessages = [];
    private string $keyUsed = '';

    public function __construct(null|string|array $key = null, mixed $default = null)
    {
        if ($key !== null) {
            $this->key(...Arr::wrap($key));
        }

        $this->default = $default;
    }

    /**
     * Set the default value for the environment variable.
     */
    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Set the environment variable key to look up.
     */
    public function key(string $key, mixed $default = null): static
    {
        $this->keys[] = $key;
        $this->default = $default;

        return $this;
    }

    /**
     * Set the environment variable key(s) to look up.
     */
    public function keys(string ...$keys): static
    {
        $this->keys = array_merge($this->keys, array_values($keys));

        return $this;
    }

    /**
     * Set the validation rules for the environment variable.
     */
    public function rules(string|array $rules, ?array $messages = null): static
    {
        $this->validationRules = $rules;

        if ($messages !== null) {
            $this->validationMessages = $messages;
        }

        return $this;
    }

    /**
     * Apply validation rules.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validate(string $key, mixed $value): void
    {
        if ($this->validationRules === '' || $this->validationRules === []) {
            return;
        }

        $validator = new Validator(
            new Translator(new ArrayLoader, 'en'),
            ['environment variable' => $value],
            ['environment variable' => $this->validationRules],
            $this->validationMessages,
        );

        /**
         * If the validation fails, repeat the validation, but
         * now reading the default validation.php file for
         * propper translation.
         */
        if (! $validator->passes()) {
            $messages = require __DIR__.'/../Translation/lang/en/validation.php';
            $translator = new Translator((new ArrayLoader)->addMessages('en', 'validation', $messages), 'en');
            $validator->setTranslator($translator);

            // Repeat validation, now with errors in english
            $validator->passes();
            $errors = implode(', ', $validator->errors()->all());

            throw new RuntimeException("Environment variable [$key] is invalid: {$errors}.");
        }
    }

    /**
     * Get the environment variable value.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function get(): mixed
    {
        $value = null;
        $this->keyUsed = '';

        // Finds the firsh not null value
        foreach ($this->keys as $key) {
            if (($value = Env::get($key)) !== null) {
                $this->keyUsed = $key;
                break;
            }
        }

        // Defaults
        if ($value === null) {
            $value = value($this->default);
            $this->keyUsed = reset($this->keys);
        }

        $this->validate($this->keyUsed, $value);

        return $value;
    }

    /**
     * Get the environment variable value as an integer.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function integer(): int
    {
        return (int) $this->get();
    }

    /**
     * Get the environment variable value as a float.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function float(): float
    {
        return (float) $this->get();
    }

    /**
     * Get the environment variable value as a boolean.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function boolean(): bool
    {
        return (bool) $this->get();
    }

    /**
     * Get the environment variable value as a string.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function string(): string
    {
        return (string) $this->get();
    }

    /**
     * Get the environment variable value as a Stringable instance.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function stringable(): Stringable
    {
        return Str::of($this->get());
    }

    /**
     * Get the environment variable value as a Collection.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function collect(string $separator = ','): Collection
    {
        $value = $this->get();

        if ($value === null || $value === '') {
            return collect();
        }

        return Str::of($value)->explode($separator);
    }

    /**
     * Get the environment variable value as an enum.
     *
     * @template TEnum of \UnitEnum|\BackedEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     *
     * @throws \RuntimeException
     */
    public function enum(string $enumClass): null|\UnitEnum|\BackedEnum
    {
        $value = $this->get();

        if (blank($value)) {
            return null;
        }

        if (! enum_exists($enumClass)) {
            throw new RuntimeException("Environment variable [$this->keyUsed] error: Enum $enumClass doesn't exist.");
        }

        $invalidValueException = new RuntimeException("Environment variable [$this->keyUsed] is not a valid value for enum $enumClass.");

        // BackedEnum
        $reflection = new \ReflectionEnum($enumClass);
        if ($reflection->isBacked()) {
            $value = $reflection->getBackingType()?->getName() === 'int' ? (int) $value : $value;

            return $enumClass::tryFrom($value) ?? throw $invalidValueException;
        }

        // UnitEnum
        if ($reflection->hasCase($value)) {
            return $reflection->getCase($value)->getValue();
        }

        throw $invalidValueException;
    }

    /**
     * Get the environment variable value as an array.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function array(string $separator = ','): array
    {
        $value = $this->get();

        if (blank($value)) {
            return [];
        }

        return explode($separator, (string) $value);
    }

}
