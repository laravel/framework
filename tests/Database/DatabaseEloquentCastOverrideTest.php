<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * Proves the memoized cast-object dispatch never bypasses a userland override.
 *
 * Every built-in cast resolves to a shared flyweight cast object whose closures
 * defer to the model at call time. These tests assert that (a) overriding any of
 * the hooks those closures call is honored through the read, write, and
 * comparison paths, and (b) the cache — which is shared across model classes for
 * the cast objects themselves — never leaks one class's overridden behavior into
 * another. Together they make the "overrides are preserved" guarantee provable
 * rather than assumed.
 */
class DatabaseEloquentCastOverrideTest extends TestCase
{
    public function testReadValueHookOverridesAreHonored()
    {
        $model = (new CastHookModel([], [
            'd' => 'date',
            'dt' => 'datetime',
            'ts' => 'timestamp',
            'f' => 'float',
            'dec' => 'decimal:2',
            'arr' => 'array',
        ]))->newFromBuilder([
            'd' => '2020-01-01',
            'dt' => '2020-01-01 00:00:00',
            'ts' => '2020-01-01 00:00:00',
            'f' => '1.5',
            'dec' => '1.5',
            'arr' => '{"a":1}',
        ]);

        $this->assertSame('date:2020-01-01', $model->d);
        $this->assertSame('datetime:2020-01-01 00:00:00', $model->dt);
        $this->assertSame('ts:2020-01-01 00:00:00', $model->ts);
        $this->assertSame('float:1.5', $model->f);
        $this->assertSame('decimal:1.5:2', $model->dec);
        $this->assertSame(['hooked' => '{"a":1}'], $model->arr);
    }

    public function testWriteValueHookOverridesAreHonored()
    {
        $model = new CastHookModel([], ['arr' => 'array', 'pw' => 'hashed']);

        $model->arr = ['a' => 1];
        $model->pw = 'secret';

        $this->assertSame('json-set:{"a":1}', $model->getAttributes()['arr']);
        $this->assertSame('hashed:secret', $model->getAttributes()['pw']);
    }

    public function testEnumHookOverrideIsHonored()
    {
        $model = (new EnumHookModel([], ['e' => CastOverrideEnum::class]))
            ->newFromBuilder(['e' => 'a']);

        $this->assertSame('enum-hook:a', $model->e);
    }

    public function testDatetimeFlyweightDoesNotLeakAcrossClasses()
    {
        $a = (new DatetimeOverrideModelA)->newFromBuilder(['dt' => '2020-01-01 00:00:00']);
        $b = (new DatetimeOverrideModelB)->newFromBuilder(['dt' => '2020-01-01 00:00:00']);

        // Resolve through class A first so the shared 'datetime' flyweight is
        // populated while reading A's instance...
        $this->assertSame('A:2020-01-01 00:00:00', $a->dt);

        // ...then class B must still use its own asDateTime() override, proving
        // the shared flyweight dispatches to the live model, not a captured one.
        $this->assertSame('B:2020-01-01 00:00:00', $b->dt);

        // And A is unchanged after B populated the cache.
        $this->assertSame('A:2020-01-01 00:00:00', $a->dt);
    }

    public function testEnumFlyweightDoesNotLeakAcrossClasses()
    {
        // All enum casts share one '@enum' flyweight; assert two classes keep
        // their own getEnumCastableAttributeValue() override.
        $a = (new EnumOverrideModelA([], ['e' => CastOverrideEnum::class]))->newFromBuilder(['e' => 'a']);
        $b = (new EnumOverrideModelB([], ['e' => CastOverrideEnum::class]))->newFromBuilder(['e' => 'a']);

        $this->assertSame('A:a', $a->e);
        $this->assertSame('B:a', $b->e);
        $this->assertSame('A:a', $a->e);
    }

    public function testCustomClassCastFlyweightDoesNotLeakAcrossClasses()
    {
        // All custom-class casts share one '@class' flyweight; assert two classes
        // keep their own getClassCastableAttributeValue() override.
        $a = (new ClassCastOverrideModelA([], ['c' => CastOverrideValueObject::class]))->newFromBuilder(['c' => 'x']);
        $b = (new ClassCastOverrideModelB([], ['c' => CastOverrideValueObject::class]))->newFromBuilder(['c' => 'x']);

        $this->assertSame('A:x', $a->c);
        $this->assertSame('B:x', $b->c);
        $this->assertSame('A:x', $a->c);
    }

    public function testResolveCastTypeOverrideIsHonoredAndClassIsolated()
    {
        // Two classes map the same raw cast string ('shared') to different types
        // via a resolveCastType() override. The cast-type cache is keyed per
        // class, so resolving one first must not make the other inherit its
        // normalization (the bug a global cache would cause).
        $asInt = (new ResolveCastTypeIntModel([], ['x' => 'shared']))->newFromBuilder(['x' => '5']);
        $asString = (new ResolveCastTypeStringModel([], ['x' => 'shared']))->newFromBuilder(['x' => '5']);

        $this->assertSame(5, $asInt->x);
        $this->assertSame('5', $asString->x);

        // ...and independent of resolution order.
        $this->assertSame('5', $asString->x);
        $this->assertSame(5, $asInt->x);
    }

    public function testIsDecimalCastOverrideIsHonored()
    {
        $model = (new DecimalCastModel([], ['m' => 'money:2']))->newFromBuilder(['m' => '1.5']);

        $this->assertSame('1.50', $model->m);
    }
}

class CastHookModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function asDate($value)
    {
        return 'date:'.$value;
    }

    protected function asDateTime($value)
    {
        return 'datetime:'.$value;
    }

    protected function asTimestamp($value)
    {
        return 'ts:'.$value;
    }

    public function fromFloat($value)
    {
        return 'float:'.$value;
    }

    protected function asDecimal($value, $decimals)
    {
        return 'decimal:'.$value.':'.$decimals;
    }

    public function fromJson($value, $asObject = false)
    {
        return ['hooked' => $value];
    }

    protected function castAttributeAsJson($key, $value)
    {
        return 'json-set:'.json_encode($value);
    }

    protected function castAttributeAsHashedString($key, #[\SensitiveParameter] $value)
    {
        return 'hashed:'.$value;
    }
}

class EnumHookModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function getEnumCastableAttributeValue($key, $value)
    {
        return 'enum-hook:'.$value;
    }
}

class DatetimeOverrideModelA extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['dt' => 'datetime'];

    protected function asDateTime($value)
    {
        return 'A:'.$value;
    }
}

class DatetimeOverrideModelB extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['dt' => 'datetime'];

    protected function asDateTime($value)
    {
        return 'B:'.$value;
    }
}

class EnumOverrideModelA extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function getEnumCastableAttributeValue($key, $value)
    {
        return 'A:'.$value;
    }
}

class EnumOverrideModelB extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function getEnumCastableAttributeValue($key, $value)
    {
        return 'B:'.$value;
    }
}

class ResolveCastTypeIntModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function resolveCastType($castType)
    {
        return $castType === 'shared' ? 'int' : parent::resolveCastType($castType);
    }
}

class ResolveCastTypeStringModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function resolveCastType($castType)
    {
        return $castType === 'shared' ? 'string' : parent::resolveCastType($castType);
    }
}

class DecimalCastModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function isDecimalCast($cast)
    {
        return str_starts_with($cast, 'money:') || parent::isDecimalCast($cast);
    }
}

class ClassCastOverrideModelA extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function getClassCastableAttributeValue($key, $value)
    {
        return 'A:'.$value;
    }
}

class ClassCastOverrideModelB extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }

    protected function getClassCastableAttributeValue($key, $value)
    {
        return 'B:'.$value;
    }
}

class CastOverrideValueObject implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): mixed
    {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes): mixed
    {
        return $value;
    }
}

enum CastOverrideEnum: string
{
    case A = 'a';
    case B = 'b';
}
