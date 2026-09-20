<?php

namespace Illuminate\Tests\Database;

use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentComparesRelatedModelsTest extends TestCase
{
    #[DataProvider('relationTypes')]
    public function testIsNotNull(string $type)
    {
        [$relation] = $this->newRelation($type, 1);

        $this->assertFalse($relation->is(null));
        $this->assertTrue($relation->isNot(null));
    }

    #[DataProvider('comparisons')]
    public function testIsComparesKeysTableAndConnection(string $type, mixed $parentKey, mixed $relatedKey, string $table, string $connection, bool $expected)
    {
        [$relation, $relatedKeyAttribute] = $this->newRelation($type, $parentKey);

        $model = (new ComparesRelatedModelsStub)
            ->setTable($table)
            ->setConnection($connection)
            ->forceFill([$relatedKeyAttribute => $relatedKey]);

        $this->assertSame($expected, $relation->is($model));
        $this->assertSame(! $expected, $relation->isNot($model));
    }

    public static function relationTypes(): Generator
    {
        foreach (['HasOne', 'MorphOne', 'BelongsTo', 'MorphTo'] as $type) {
            yield $type => [$type];
        }
    }

    public static function comparisons(): Generator
    {
        $scenarios = [
            'same keys' => ['abc', 'abc', 'table', 'connection', true],
            'integer parent key' => [1, '1', 'table', 'connection', true],
            'integer related key' => ['1', 1, 'table', 'connection', true],
            'integer keys' => [1, 1, 'table', 'connection', true],
            'null parent key' => [null, 1, 'table', 'connection', false],
            'null related key' => [1, null, 'table', 'connection', false],
            'both keys null' => [null, null, 'table', 'connection', false],
            'both keys empty strings' => ['', '', 'table', 'connection', false],
            'different keys' => [1, 2, 'table', 'connection', false],
            'different table' => [1, 1, 'other_table', 'connection', false],
            'different connection' => [1, 1, 'table', 'other_connection', false],
        ];

        foreach (['HasOne', 'MorphOne', 'BelongsTo', 'MorphTo'] as $type) {
            foreach ($scenarios as $name => $scenario) {
                yield "$type: $name" => [$type, ...$scenario];
            }
        }
    }

    /**
     * @return array{0: \Illuminate\Database\Eloquent\Relations\Relation, 1: string}
     */
    protected function newRelation(string $type, mixed $parentKey): array
    {
        $related = (new ComparesRelatedModelsStub)->setTable('table')->setConnection('connection');
        $builder = (new Builder((new SQLiteConnection(new PDO('sqlite::memory:')))->query()))->setModel($related);
        $parent = new ComparesRelatedModelsStub;

        return match ($type) {
            'HasOne' => [new HasOne($builder, $parent->forceFill(['id' => $parentKey]), 'foreign_key', 'id'), 'foreign_key'],
            'MorphOne' => [new MorphOne($builder, $parent->forceFill(['id' => $parentKey]), 'morph_type', 'morph_id', 'id'), 'morph_id'],
            'BelongsTo' => [new BelongsTo($builder, $parent->forceFill(['foreign_key' => $parentKey]), 'foreign_key', 'id', 'relation'), 'id'],
            'MorphTo' => [new MorphTo($builder, $parent->forceFill(['foreign_key' => $parentKey]), 'foreign_key', 'id', 'morph_type', 'relation'), 'id'],
        };
    }
}

class ComparesRelatedModelsStub extends Model
{
    protected $guarded = [];
    protected $primaryKey = 'uuid';
}
