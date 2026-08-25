<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentAsVectorCastTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        Model::unsetConnectionResolver();
    }

    public function testGetDecodesBinaryVector()
    {
        $this->useGrammar(MariaDbGrammar::class);

        $model = new AsVectorTestModel;
        $model->setRawAttributes(['embedding' => pack('g*', 0.5, -1.25, 3)]);

        $this->assertSame([0.5, -1.25, 3.0], $model->embedding);
    }

    public function testGetDecodesBinaryVectorBeginningWithOpeningBracketByte()
    {
        $this->useGrammar(MariaDbGrammar::class);

        $model = new AsVectorTestModel;
        $model->setRawAttributes(['embedding' => pack('g*', 1.0000108480453491)]);

        $this->assertSame([1.0000108480453491], $model->embedding);
    }

    public function testGetDecodesTextVector()
    {
        $this->useGrammar(PostgresGrammar::class);

        $model = new AsVectorTestModel;
        $model->setRawAttributes(['embedding' => '[0.5,-1.25,3]']);

        $this->assertSame([0.5, -1.25, 3.0], $model->embedding);
    }

    public function testGetReturnsNullForNullValue()
    {
        $this->useGrammar(MariaDbGrammar::class);

        $model = new AsVectorTestModel;
        $model->setRawAttributes(['embedding' => null]);

        $this->assertNull($model->embedding);
    }

    public function testSetOnMariaDbWrapsVectorInVecFromText()
    {
        $grammar = $this->useGrammar(MariaDbGrammar::class);

        $model = new AsVectorTestModel;
        $model->embedding = [0.5, -1.25, 3.75];

        $attribute = $model->getAttributes()['embedding'];

        $this->assertInstanceOf(Expression::class, $attribute);
        $this->assertSame("vec_fromtext('[0.5,-1.25,3.75]')", $attribute->getValue($grammar));
    }

    public function testSetOnPostgresStoresJson()
    {
        $this->useGrammar(PostgresGrammar::class);

        $model = new AsVectorTestModel;
        $model->embedding = [0.5, -1.25, 3.75];

        $this->assertSame('[0.5,-1.25,3.75]', $model->getAttributes()['embedding']);
    }

    public function testSetAcceptsArrayable()
    {
        $this->useGrammar(PostgresGrammar::class);

        $model = new AsVectorTestModel;
        $model->embedding = new Collection([0.5, -1.25, 3.75]);

        $this->assertSame('[0.5,-1.25,3.75]', $model->getAttributes()['embedding']);
    }

    public function testSetStoresNullAsNull()
    {
        $this->useGrammar(MariaDbGrammar::class);

        $model = new AsVectorTestModel;
        $model->embedding = null;

        $this->assertNull($model->getAttributes()['embedding']);
    }

    public function testSetRejectsNonArrayValues()
    {
        $this->useGrammar(MariaDbGrammar::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [embedding] attribute must be an array of floats or an Arrayable instance.');

        $model = new AsVectorTestModel;
        $model->embedding = 'not a vector';
    }

    public function testVectorCanBeReadBackBeforeSavingOnMariaDb()
    {
        $this->useGrammar(MariaDbGrammar::class);

        $model = new AsVectorTestModel;
        $model->embedding = [0.5, -1.25, 3];

        $this->assertSame([0.5, -1.25, 3.0], $model->embedding);
    }

    public function testVectorCanBeReadBackBeforeSavingOnPostgres()
    {
        $this->useGrammar(PostgresGrammar::class);

        $model = new AsVectorTestModel;
        $model->embedding = [0.5, -1.25, 3];

        $this->assertSame([0.5, -1.25, 3.0], $model->embedding);
    }

    protected function useGrammar(string $grammar)
    {
        $connection = m::mock(Connection::class);
        $grammar = new $grammar($connection);
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('connection')->andReturn($connection);

        Model::setConnectionResolver($resolver);

        return $grammar;
    }
}

class AsVectorTestModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'embedding' => AsVector::class,
    ];
}
