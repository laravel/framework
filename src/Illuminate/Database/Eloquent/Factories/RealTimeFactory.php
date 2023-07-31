<?php

namespace Illuminate\Database\Eloquent\Factories;

use BackedEnum;
use DateTime;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\Type;
use Exception;
use Illuminate\Database\Concerns\ManagesTypeMapping;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class RealTimeFactory extends Factory
{
    use ManagesTypeMapping;

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
        return $this->configure()
            ->getColumnsFromModel()
            ->reject(fn (Column $column) => $column->getAutoincrement() || $this->isForeignKey($column) || $this->isPrimaryKey($column))
            ->map(fn (Column $column) => $this->value($column))
            ->all();
    }

    /**
     * Configure the factory.
     */
    public function configure(): self
    {
        if (! interface_exists('Doctrine\DBAL\Driver')) {
            throw new Exception('Real-time factories require the Doctrine DBAL (doctrine/dbal) package.');
        }

        $modelName = $this->modelName();
        $this->modelInstance = new $modelName;
        $connection = $this->modelInstance->getConnection();
        $this->schema = $connection->getDoctrineSchemaManager();
        $this->table = $this->modelInstance->getConnection()->getTablePrefix().$this->modelInstance->getTable();
        $this->registerTypeMappings($connection->getDoctrineConnection()->getDatabasePlatform());

        return $this;
    }

    /**
     * Set the model for the factory.
     */
    public function forModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Create a new instance of the factory builder with the given mutated properties.
     *
     * @return static
     */
    protected function newInstance(array $arguments = [])
    {
        return parent::newInstance($arguments)
            ->forModel($this->model)
            ->configure();
    }

    /**
     * Get a new factory instance for the given attributes.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return static
     */
    public static function new($attributes = [])
    {
        throw new RuntimeException('Real-time factories cannot be instantiated with new()');
    }

    /**
     * Get the table columns from the model.
     */
    protected function getColumnsFromModel(): Collection
    {
        $columns = $this->schema->listTableColumns($this->table);

        return collect($columns)->keyBy(fn ($column) => $column->getName());
    }

    /**
     * Generate a value for the given column.
     *
     * @return void
     */
    protected function value(Column $column): mixed
    {
        if ($value = $this->guessValue($column->getName())) {
            return $value;
        }

        return ($value = $this->valueFromCast($column)) ?
            $value :
            $this->valueFromColumn($column);
    }

    /**
     * Determine whether the given column is a foreign key.
     */
    protected function isForeignKey(Column $column): bool
    {
        return collect($this->schema->listTableForeignKeys($this->table))
            ->filter(fn ($foreignKey) => in_array($column->getName(), $foreignKey->getLocalColumns()))
            ->isNotEmpty();
    }

    /**
     * Determine whether the given column is the primary key.
     */
    protected function isPrimaryKey(Column $column): bool
    {
        return collect($this->schema->listTableIndexes($this->table))
            ->some(fn (Index $index) => $index->isPrimary() && in_array($column->getName(), $index->getColumns()));
    }

    /**
     * Generate a value for the given column.
     */
    protected function valueFromColumn(Column $column): mixed
    {
        if (! $column->getNotnull() && $column->getDefault() === null) {
            return null;
        }

        if ($value = $column->getDefault()) {
            return $value;
        }

        return match (true) {
            $this->isIntegerType($column->getType()) => $this->integerValue(),
            $this->isDateType($column->getType()) => $this->dateValue(),
            $this->isDecimalType($column->getType()) => $this->decimalValue($column->getPrecision() ?: 10, $column->getScale() ?: 2),
            $column->getType() instanceof TimeType => fake()->time(),
            $column->getType() instanceof BlobType,$column->getType() instanceof TextType => fake()->text(),
            $column->getType() instanceof BooleanType => $this->booleanValue(),
            $column->getType() instanceof JsonType,$column->getType() instanceof ArrayType => $this->jsonValue(),
            default => $this->stringValue($column->getLength()),
        };
    }

    /**
     * Generate a value using the defined cast for the column.
     */
    protected function valueFromCast(Column $column): mixed
    {
        if (in_array($column->getName(), $this->modelInstance->getDates())) {
            return $this->dateValue();
        }

        if (! $key = $this->modelInstance->getCasts()[$column->getName()] ?? null) {
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

        if ($this->isEnumCollectionCastable($key)) {
            return $this->enumCollectionValue($key);
        }

        if ($this->isEnumCastable($key)) {
            return $this->enumValue($key);
        }

        if ($this->isStringCastable($key)) {
            return $this->stringValue($column->getLength());
        }

        return null;
    }

    /**
     * Determine whether the given cast is an array cast.
     */
    protected function isArrayCastable(string $key): bool
    {
        return in_array($key, ['array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object', AsArrayObject::class, AsCollection::class, AsEncryptedArrayObject::class, AsEncryptedCollection::class]);
    }

    /**
     * Determine whether the given cast is an integer cast.
     */
    protected function isIntegerCastable(string $key): bool
    {
        return in_array($key, ['int', 'integer']);
    }

    /**
     * Determine whether the given type is an integer type.
     */
    protected function isIntegerType(Type $type): bool
    {
        return $type instanceof PhpIntegerMappingType ||
            $type instanceof BigIntType ||
            $type instanceof IntegerType ||
            $type instanceof SmallIntType;
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
     * Determine whether the given type is a decimal type.
     */
    protected function isDecimalType(Type $type): bool
    {
        return $type instanceof DecimalType ||
            $type instanceof FloatType;
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
     * Determine whether the given cast is a date cast.
     */
    protected function isDateType(Type $type): bool
    {
        return $type instanceof DateTimeType;
    }

    /**
     * Determine whether the given cast is a timestamp cast.
     */
    protected function isTimestampCastable(string $key): bool
    {
        return $key === 'timestamp';
    }

    /**
     * Determine whether the given cast is an enum collection cast.
     */
    protected function isEnumCollectionCastable(string $key): bool
    {
        return in_array(Str::before($key, ':'), [AsEnumCollection::class, AsEnumArrayObject::class]) &&
            $this->isEnumCastable($key);
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
            'encrypted',
            AsStringable::class,
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
     * Generate a JSON value.
     */
    protected function jsonValue(): string
    {
        return json_encode($this->arrayValue());
    }

    /**
     * Generate an integer value.
     */
    protected function integerValue(): int
    {
        return fake()->randomDigit();
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
    protected function decimalValue(int $max = 100, int $decimals = 2): float
    {
        return fake()->randomFloat($decimals, 0, $max);
    }

    /**
     * Generate a boolean value.
     */
    protected function booleanValue(): bool
    {
        return fake()->boolean();
    }

    /**
     * Generate a date value.
     */
    protected function dateValue(): DateTime
    {
        return fake()->dateTime();
    }

    /**
     * Generate a timestamp value.
     */
    protected function timestampValue(): int
    {
        return fake()->unixTime();
    }

    /**
     * Generate an enum collection value.
     */
    protected function enumCollectionValue(string $enum): mixed
    {
        return collect(range(1, 5))->map(fn () => $this->enumValue($enum))
            ->all();
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
    protected function stringValue(?int $columnLength): string
    {
        return fake()->text($columnLength ?? 10);
    }

    /**
     * Guess the value of a column based on its name.
     */
    protected function guessValue(string $column): mixed
    {
        $guessable = $this->guessableValues();

        return isset($guessable[$column]) ? $guessable[$column]() : null;
    }

    /**
     * Get a list of guessable values.
     *
     * @return array<string, callable>
     */
    protected function guessableValues(): array
    {
        return [
            'email' => fn () => fake()->safeEmail(),
            'e_mail' => fn () => fake()->safeEmail(),
            'email_address' => fn () => fake()->safeEmail(),
            'name' => fn () => fake()->name(),
            'first_name' => fn () => fake()->firstName(),
            'last_name' => fn () => fake()->lastName(),
            'login' => fn () => fake()->userName(),
            'username' => fn () => fake()->userName(),
            'dob' => fn () => fake()->date(),
            'date_of_birth' => fn () => fake()->date(),
            'uuid' => fn () => fake()->uuid(),
            'url' => fn () => fake()->url(),
            'website' => fn () => fake()->url(),
            'phone' => fn () => fake()->phoneNumber(),
            'phone_number' => fn () => fake()->phoneNumber(),
            'telephone' => fn () => fake()->phoneNumber(),
            'tel' => fn () => fake()->phoneNumber(),
            'town' => fn () => fake()->city(),
            'city' => fn () => fake()->city(),
            'zip' => fn () => fake()->postcode(),
            'zip_code' => fn () => fake()->postcode(),
            'zipcode' => fn () => fake()->postcode(),
            'postal_code' => fn () => fake()->postcode(),
            'postalcode' => fn () => fake()->postcode(),
            'post_code' => fn () => fake()->postcode(),
            'postcode' => fn () => fake()->postcode(),
            'state' => fn () => fake()->state(),
            'province' => fn () => fake()->state(),
            'county' => fn () => fake()->state(),
            'country' => fn () => fake()->country(),
            'currency_code' => fn () => fake()->currencyCode(),
            'currency' => fn () => fake()->currencyCode(),
            'company' => fn () => fake()->company(),
            'company_name' => fn () => fake()->company(),
            'companyname' => fn () => fake()->company(),
            'employer' => fn () => fake()->company(),
            'title' => fn () => fake()->title(),
        ];
    }
}
