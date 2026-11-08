<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseEloquentRouteBindingTest extends TestCase
{
    use VerifiesDoubles;

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
        $query = Double::for(Builder::class);
        $query->expects('where')->never();

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
        $query = Double::for(Builder::class);
        $query->expects('where')->with('id', $value)->returns($query);

        $this->assertSame($query, (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, $value));
    }

    public function testItValidatesQualifiedKeyColumnsFromChildBindings()
    {
        $query = Double::for(Builder::class);
        $query->expects('where')->never();

        $this->expectException(ModelNotFoundException::class);

        (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, 'abc', 'route_binding_int_key_models.id');
    }

    public function testItDoesNotValidateNonKeyBindingFields()
    {
        $query = Double::for(Builder::class);
        $query->expects('where')->with('slug', 'abc')->returns($query);

        (new RouteBindingIntKeyModel)->resolveRouteBindingQuery($query, 'abc', 'slug');
    }

    public function testItDoesNotValidateModelsWithStringKeys()
    {
        $query = Double::for(Builder::class);
        $query->expects('where')->with('id', 'abc')->returns($query);

        (new RouteBindingStringKeyModel)->resolveRouteBindingQuery($query, 'abc');
    }

    public function testItDoesNotValidateNonIncrementingModelsWithTheDefaultKeyType()
    {
        $query = Double::for(Builder::class);
        $query->expects('where')->with('id', 'US')->returns($query);

        (new RouteBindingNonIncrementingKeyModel)->resolveRouteBindingQuery($query, 'US');
    }

    public function testItDoesNotValidateNullValuesForModelsWithStringKeys()
    {
        $query = Double::for(Builder::class);
        $query->expects('where')->with('id', null)->returns($query);

        (new RouteBindingStringKeyModel)->resolveRouteBindingQuery($query, null);
    }

    public function testTheInvalidKeyHandlerMayBeOverridden()
    {
        $query = Double::for(Builder::class);
        $query->expects('where')->never();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid id: abc');

        (new RouteBindingCustomExceptionModel)->resolveRouteBindingQuery($query, 'abc');
    }

    public function testTheExceptionCarriesTheModelAndValue()
    {
        $query = Double::for(Builder::class);

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

class RouteBindingNonIncrementingKeyModel extends Model
{
    public $incrementing = false;
}

class RouteBindingCustomExceptionModel extends Model
{
    protected function handleInvalidRouteKey(mixed $value, string $field): never
    {
        throw new RuntimeException("Invalid {$field}: {$value}");
    }
}
