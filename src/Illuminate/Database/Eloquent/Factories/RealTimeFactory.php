<?php

namespace Illuminate\Database\Eloquent\Factories;

use BackedEnum;
use DateTime;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Concerns\InteractsWithTables;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RealTimeFactory extends Factory
{
    use InteractsWithTables;

    /**
     * An instance of the factory's corresponding model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelInstance;

    /**
     * The database schema manager for the model.
     *
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $schema;

    /**
     * The table name for the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $this->configure();

        return $this->getColumnsFromModel()
            ->map(fn (Column $column) => $this->value($column))
            ->all();
    }

    /**
     * Configure the factory.
     */
    public function configure(): self
    {
        $modelName = $this->modelName();
        $this->modelInstance = new $modelName;
        $connection = $this->modelInstance->getConnection();
        $this->schema = $connection->getDoctrineSchemaManager();
        $this->table = $this->modelInstance->getConnection()->getTablePrefix().$this->modelInstance->getTable();
        $this->registerTypeMappings($connection->getDoctrineConnection()->getDatabasePlatform());

        return $this;
    }

    /**
     * Get the table columns from the model.
     */
    protected function getColumnsFromModel(): Collection
    {
        $columns = $this->schema->listTableColumns($this->table);

        return collect($columns);
    }

    /**
     * Generate a value for the given column.
     *
     * @return void
     */
    protected function value(Column $column): mixed
    {
        if ($column->getAutoincrement()) {
            return null;
        }

        return ($value = $this->valueFromCast($column->getName())) ?
            $value :
            $this->valueFromType($column->getType());
    }

    /**
     * Generate a value for the given column type.
     */
    protected function valueFromType(Type $type): mixed
    {
        return match (true) {
            $type instanceof PhpIntegerMappingType => fake()->randomDigit,
            $type instanceof DateTimeType => fake()->dateTime,
            default => fake()->word
        };
    }

    /**
     * Generate a value using the defined cast for the column.
     */
    protected function valueFromCast(string $column): mixed
    {
        if (! $key = $this->modelInstance->getCasts()[$column] ?? null) {
            return null;
        }

        if ($this->isArrayCastable($key)) {
            return $this->arrayValue();
        }

        if ($this->isIntegerCastable($key)) {
            return $this->integerValue();
        }

        if ($this->isRealCastable($key)) {
            return $this->realValue();
        }

        if ($precision = $this->isDecimalCastable($key)) {
            return $this->decimalValue($precision);
        }

        if ($this->isBooleanCastable($key)) {
            return $this->booleanValue();
        }

        if ($this->isDateCastable($key)) {
            return $this->dateValue();
        }

        if ($this->isTimestampCastable($key)) {
            return $this->timestampValue();
        }

        if ($this->isEnumCastable($key)) {
            return $this->enumValue($key);
        }

        if ($this->isStringCastable($key)) {
            return $this->stringValue();
        }

        return null;
    }

    protected function isArrayCastable(string $key): bool
    {
        return in_array($key, ['array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object']);
    }

    /**
     * Determine whether the given cast is an integer cast.
     */
    protected function isIntegerCastable(string $key): bool
    {
        return in_array($key, ['int', 'integer']);
    }

    /**
     * Determine whether the given cast is a real number cast.
     */
    protected function isRealCastable(string $key): bool
    {
        return in_array($key, ['real', 'float', 'double']);
    }

    /**
     * Determine whether the given cast is a decimal cast.
     */
    protected function isDecimalCastable(string $key): int|bool
    {
        if (Str::startsWith($key, 'decimal')) {
            return (int) Str::after($key, ':');
        }

        return false;
    }

    /**
     * Determine whether the given cast is a boolean cast.
     */
    protected function isBooleanCastable(string $key): bool
    {
        return in_array($key, ['bool', 'boolean']);
    }

    /**
     * Determine whether the given cast is a date cast.
     */
    protected function isDateCastable(string $key): bool
    {
        return in_array($key, ['date', 'datetime', 'immutable_date', 'immutable_datetime']);
    }

    /**
     * Determine whether the given cast is a timestamp cast.
     */
    protected function isTimestampCastable(string $key): bool
    {
        return $key === 'timestamp';
    }

    /**
     * Determine whether the given cast is an enum cast.
     */
    protected function isEnumCastable(string $key): bool
    {
        return enum_exists(Str::after($key, ':'));
    }

    /**
     * Determine whether the given cast is a string cast.
     */
    protected function isStringCastable(string $key): bool
    {
        return in_array($key, [
            'string',
            'encrypted:string',
        ]);
    }

    /**
     * Generate an array value.
     */
    protected function arrayValue(): array
    {
        return fake()->words(5);
    }

    /**
     * Generate an integer value.
     */
    protected function integerValue(): int
    {
        return fake()->randomDigit;
    }

    /**
     * Generate a real number value.
     */
    protected function realValue(): float
    {
        return fake()->randomFloat(2, 0, 100);
    }

    /**
     * Generate a decimal value.
     */
    protected function decimalValue(int $precision): float
    {
        return fake()->randomFloat($precision, 0, 100);
    }

    /**
     * Generate a boolean value.
     */
    protected function booleanValue(): bool
    {
        return fake()->boolean;
    }

    /**
     * Generate a date value.
     */
    protected function dateValue(): DateTime
    {
        return fake()->dateTime;
    }

    /**
     * Generate a timestamp value.
     */
    protected function timestampValue(): int
    {
        return fake()->unixTime;
    }

    /**
     * Generate an enum value.
     */
    protected function enumValue(string $enum): mixed
    {
        $enum = Str::after($enum, ':');
        $case = Arr::random($enum::cases());

        return $case instanceof BackedEnum ? $case->value : $case->name;
    }

    /**
     * Generate a string value.
     */
    protected function stringValue(): string
    {
        return fake()->word;
    }
}
