<?php

namespace Illuminate\Database\Eloquent\Factories;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\DBAL\EnumType;
use Illuminate\Database\Eloquent\Model;

class RealTimeFactory extends Factory
{
    protected Model $modelInstance;

    /**
     * A map of database column types.
     *
     * @var array
     */
    protected $typeMappings = [
        'bit' => 'string',
        'citext' => 'string',
        'enum' => 'enum',
        'geometry' => 'string',
        'geomcollection' => 'string',
        'linestring' => 'string',
        'ltree' => 'string',
        'multilinestring' => 'string',
        'multipoint' => 'string',
        'multipolygon' => 'string',
        'point' => 'string',
        'polygon' => 'string',
        'sysname' => 'string',
    ];

    protected function registerTypeMappings(AbstractPlatform $platform)
    {
        Type::addType('enum', new EnumType);

        foreach ($this->typeMappings as $type => $value) {
            $platform->registerDoctrineTypeMapping($type, $value);
        }
    }

    protected function getColumnsFromModel()
    {
        $modelName = $this->modelName();
        $this->modelInstance = new $modelName;
        $connection = $this->modelInstance->getConnection();
        $schema = $connection->getDoctrineSchemaManager();
        $this->registerTypeMappings($connection->getDoctrineConnection()->getDatabasePlatform());
        $table = $this->modelInstance->getConnection()->getTablePrefix().$this->modelInstance->getTable();
        $columns = $schema->listTableColumns($table);

        return collect($columns);
    }

    public function definition(): array
    {
        $modelName = $this->modelName();
        $columns = $this->getColumnsFromModel();
        return $columns
            ->map(fn (Column $column) => $this->makeValue($column))
            ->all();
    }

    protected function makeValue(Column $column)
    {
        if ($column->getAutoincrement()) {
            return null;
        }

        return ($value = $this->makeValueFromCast($column)) ?
            $value :
            $this->getFakeType($column->getType());
    }

    protected function getFakeType(Type $type)
    {
        return match (true) {
            $type instanceof PhpIntegerMappingType => fake()->randomDigit,
            $type instanceof DateTimeType => fake()->dateTime,
            default => fake()->word
        };
    }

    protected function getFakeValue(string $cast)
    {
        return match ($cast) {
            default => null,
        };
    }

    protected function makeValueFromCast(Column $column)
    {
        return match (true) {
            $this->modelInstance->hasCast($column->getName(), 'array') => fake()->words(5),
            $this->modelInstance->hasCast($column->getName(), 'collection') => collect(fake()->words(5)),
            $this->modelInstance->hasCast($column->getName(), 'encrypted') => fake()->word,
            default => null,
        };
    }
}
