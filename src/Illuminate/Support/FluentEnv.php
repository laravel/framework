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
     * Create a validator instance for the environment variable.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validator(string $key, mixed $value): ?Validator
    {
        if ($this->validationRules === '' || $this->validationRules === []) {
            return null;
        }

        return new Validator(
            new Translator(new ArrayLoader, 'en'),
            [$key => $value],
            [$key => $this->validationRules],
            $this->validationMessages,
        );
    }

    /**
     * Get the environment variable value.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function get(): mixed
    {
        foreach ($this->keys as $key) {
            if (($value = Env::get($key)) !== null) {
                $this->validator($key, $value)?->validate();

                return $value;
            }
        }

        $default = value($this->default);

        $this->validator(reset($this->keys).' (default)', $default)?->validate();

        return $default;
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
        return Str::of($this->get())->explode($separator);
    }

    /**
     * Get the environment variable value as an enum.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function enum(string $enumClass): null|\UnitEnum|\BackedEnum
    {
        $value = $this->get();

        if ($value === null || ! enum_exists($enumClass)) {
            return null;
        }

        $reflection = new \ReflectionEnum($enumClass);

        // BackedEnum
        if ($reflection->isBacked()) {
            if ($reflection->getBackingType()?->getName() === 'int') {
                $value = (int) $value;
            }

            return $enumClass::tryFrom($value);
        }

        // UnitEnum
        if ($reflection->hasCase($value)) {
            return $reflection->getCase($value)->getValue();
        }

        return null;
    }

    /**
     * Get the environment variable value as an array.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function array(string $separator = ',', string $cast = ''): array
    {
        $values = explode($separator, $this->string());

        if ($cast !== '') {
            $values = collect($values)->map(fn ($value) => match ($cast) {
                'bool' | 'boolean' => $value === 'false' || $value === '(false)' ? false : (bool) $value,
                'float' | 'double' => (float) $value,
                'int' | 'integer' => (int) $value,
                'string' => (string) $value,
                default => throw new RuntimeException("Invalid cast type: {$cast}"),
            })->toArray();
        }

        return $values;
    }
}
