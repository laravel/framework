<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyThroughTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2023-01-01 00:00:00');
    }
    protected function mockConnectionForModel(Model $model, string $database): void
    {
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
        $processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
        $grammar = new $grammarClass;
        $processor = new $processorClass;
        $connection = Mockery::mock(ConnectionInterface::class, ['getQueryGrammar' => $grammar, 'getPostProcessor' => $processor]);
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new Builder($connection, $grammar, $processor);
        });
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $resolver = Mockery::mock(ConnectionResolverInterface::class, ['connection' => $connection]);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);
    }
}

/**
 * @property string $id
 */
class HasManyThroughChild extends Model
{
    public $incrementing = false;

    protected $table = 'child';

    protected $keyType = 'string';
}

/**
 * @property string $id
 * @property string $parent_id
 * @property string $child_id
 */
class HasManyThroughPivot extends Model
{
    public $incrementing = false;

    protected $table = 'pivot';

    protected $keyType = 'string';
}

/**
 * @property string $id
 */
class HasManyThroughParent extends Model
{
    public $incrementing = false;

    protected $table = '';

    protected $keyType = 'string';

    public function children(): HasManyThrough
    {
        return $this->hasManyThrough(
            HasManyThroughChild::class,
            HasManyThroughPivot::class,
            'parent_id',
            'id',
            'id',
            'child_id',
        );
    }
}

