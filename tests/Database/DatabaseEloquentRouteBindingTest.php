<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseEloquentRouteBindingTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public static function invalidIntegerKeyProvider(): array
    {
        return [
            'null' => [null],
            'letters' => ['abc'],
            'trailing letters' => ['12abc'],
            'leading letters' => ['abc12'],
            'thousands separator' => ['1,234'],
            'decimal' => ['1.5'],
            'hex' => ['0x1A'],
            'empty string' => [''],
            'whitespace only' => ['   '],
            'beyond php int range' => ['99999999999999999999'],
            'repeated identifier' => ['12341234123412341234'],
            'negative beyond range' => ['-99999999999999999999'],
        ];
    }

    #[DataProvider('invalidIntegerKeyProvider')]
    public function testItRejectsValuesThatCannotAddressAnIntegerKeyWithoutQuerying($value)
    {
        $query = m::mock(Builder::class);
        $query->shouldNotReceive('where');

        $this->expectException(ModelNotFoundException::class);

        (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, $value);
    }

    public static function validIntegerKeyProvider(): array
    {
        return [
            'numeric string' => ['12'],
            'integer' => [12],
            'leading zeros' => ['0012'],
            'surrounding whitespace' => [' 12 '],
            'zero' => ['0'],
            'padded zero' => ['000'],
            'negative' => ['-5'],
            'signed positive' => ['+5'],
            'php int max' => [(string) PHP_INT_MAX],
        ];
    }

    #[DataProvider('validIntegerKeyProvider')]
    public function testItPassesThroughValuesTheDatabaseCanStillMatch($value)
    {
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', $value)->andReturnSelf();

        $this->assertSame($query, (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, $value));
    }

    public function testItValidatesQualifiedKeyColumnsFromChildBindings()
    {
        $query = m::mock(Builder::class);
        $query->shouldNotReceive('where');

        $this->expectException(ModelNotFoundException::class);

        (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, 'abc', 'route_binding_int_key_models.id');
    }

    public function testItDoesNotValidateNonKeyBindingFields()
    {
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('slug', 'abc')->andReturnSelf();

        (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, 'abc', 'slug');
    }

    public function testItDoesNotValidateModelsWithStringKeys()
    {
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', 'abc')->andReturnSelf();

        (new RouteBindingStringKeyModel)->resolveRouteBindingQuery($query, 'abc');
    }

    public function testItDoesNotValidateNullValuesForModelsWithStringKeys()
    {
        $query = m::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('id', null)->andReturnSelf();

        (new RouteBindingStringKeyModel)->resolveRouteBindingQuery($query, null);
    }

    public function testTheInvalidKeyHandlerMayBeOverridden()
    {
        $query = m::mock(Builder::class);
        $query->shouldNotReceive('where');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid id: abc');

        (new RouteBindingCustomExceptionModel)->resolveRouteBindingQuery($query, 'abc');
    }

    public function testTheExceptionCarriesTheModelAndValue()
    {
        $query = m::mock(Builder::class);

        try {
            (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, 'abc');
        } catch (ModelNotFoundException $e) {
            $this->assertSame(RouteBindingIntKeyModel::class, $e->getModel());
            $this->assertSame(['abc'], $e->getIds());

            return;
        }

        $this->fail('No exception was thrown.');
    }
}

class RouteBindingIntKeyModel extends Model
{
    //
}

class RouteBindingStringKeyModel extends Model
{
    protected $keyType = 'string';
}

class RouteBindingCustomExceptionModel extends Model
{
    protected function handleInvalidRouteKey(mixed $value, string $field): never
    {
        throw new RuntimeException("Invalid {$field}: {$value}");
    }
}
