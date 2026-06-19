<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exercises attribute-cast equivalence (dirty checking) across every built-in
 * cast type and the edge cases that distinguish a real change from an equivalent
 * re-assignment: differently typed scalars (e.g. the string '1' a driver may
 * return for an integer column vs the int 1), differently formatted but
 * equivalent dates, and JSON that re-encodes to a different string but the same
 * structure. These paths are not exercised by the sqlite-backed suite, so they
 * are asserted here directly against crafted "stored" values.
 */
class DatabaseEloquentCastEquivalenceTest extends TestCase
{
    /**
     * @return array<string, array{string|object, mixed, mixed, bool}>
     */
    public static function dirtyCases(): array
    {
        return [
            // [cast, stored "original" value, freshly set value, expected isDirty]

            // Integers — a string original (as some drivers return) is not dirty.
            'int: string original vs int set' => ['integer', '1', 1, false],
            'int: unchanged' => ['integer', 1, 1, false],
            'int: changed' => ['integer', 1, 2, true],
            'int: zero forms' => ['integer', '0', 0, false],
            'int: null/null' => ['integer', null, null, false],

            // Floats.
            'float: string vs float' => ['float', '1.5', 1.5, false],
            'float: int vs float one' => ['float', '1.0', 1, false],
            'float: changed' => ['float', 1.5, 1.6, true],

            // Decimals (precision-normalized).
            'decimal: trailing zero' => ['decimal:2', '1.00', '1.0', false],
            'decimal: string vs int' => ['decimal:2', '1.00', 1, false],
            'decimal: rounds equal' => ['decimal:2', '1.23', '1.234', false],

            // Strings.
            'string: string vs int' => ['string', '1', 1, false],
            'string: changed' => ['string', 'a', 'b', true],

            // Booleans.
            'bool: string vs bool' => ['boolean', '1', true, false],
            'bool: int vs bool' => ['boolean', 1, true, false],
            'bool: falsey' => ['boolean', '0', false, false],
            'bool: changed' => ['boolean', true, false, true],

            // Object / array / json — whitespace differences decode equal.
            'object: same' => ['object', '{"a":1,"b":2}', ['a' => 1, 'b' => 2], false],
            'object: whitespace' => ['object', '{"a": 1, "b": 2}', ['a' => 1, 'b' => 2], false],
            'object: reordered keys' => ['object', '{"a":1,"b":2}', ['b' => 2, 'a' => 1], true],
            'array: same' => ['array', '{"a":1,"b":2}', ['a' => 1, 'b' => 2], false],
            'array: whitespace' => ['array', '{"a": 1, "b": 2}', ['a' => 1, 'b' => 2], false],
            'array: list reordered' => ['array', '[1,2,3]', [3, 2, 1], true],
            'json:unicode same' => ['json:unicode', '{"a":"é"}', ['a' => 'é'], false],
            'collection: same' => ['collection', '[1,2,3]', [1, 2, 3], false],
            'collection: whitespace' => ['collection', '{"a": 1}', ['a' => 1], false],

            // Dates — an equivalent instant in a different textual form is not dirty.
            'date: equivalent format' => ['date', '2025-01-01', '2025-01-01 00:00:00', false],
            'date: changed' => ['date', '2025-01-01', '2025-01-02', true],
            'datetime: equivalent format' => ['datetime', '2025-01-01', '2025-01-01 00:00:00', false],
            'datetime: changed' => ['datetime', '2025-01-01 12:00:00', '2025-01-01 13:00:00', true],
            'custom datetime: equivalent' => ['datetime:Y-m-d', '2025-01-01', '2025-01-01 00:00:00', false],
            'immutable date: equivalent' => ['immutable_date', '2025-01-01', '2025-01-01 00:00:00', false],
            'immutable datetime: equivalent' => ['immutable_datetime', '2025-01-01', '2025-01-01 00:00:00', false],

            // Timestamps.
            'timestamp: string vs int' => ['timestamp', '1700000000', 1700000000, false],
            'timestamp: changed' => ['timestamp', 1700000000, 1700000001, true],

            // Backed enums — same case from a differently typed scalar is not dirty.
            'int enum: string original vs case' => [CastEquivalenceIntEnum::class, '1', CastEquivalenceIntEnum::A, false],
            'int enum: int original vs case' => [CastEquivalenceIntEnum::class, 1, CastEquivalenceIntEnum::A, false],
            'int enum: changed case' => [CastEquivalenceIntEnum::class, 1, CastEquivalenceIntEnum::B, true],
            'string enum: same' => [CastEquivalenceStringEnum::class, 'a', CastEquivalenceStringEnum::A, false],
            'string enum: changed' => [CastEquivalenceStringEnum::class, 'a', CastEquivalenceStringEnum::B, true],

            // Custom cast (round-trips through set()).
            'custom cast: round trip' => [CastEquivalenceReverseCast::class, 'cba', 'abc', false],
        ];
    }

    #[DataProvider('dirtyCases')]
    public function testCastDirtyEquivalence($cast, $original, $set, bool $expectedDirty)
    {
        $model = (new CastEquivalenceModel([], ['value' => $cast]))->newFromBuilder(['value' => $original]);

        $model->value = $set;

        $this->assertSame($expectedDirty, $model->isDirty('value'));
        $this->assertSame($expectedDirty, array_key_exists('value', $model->getDirty()));
    }

    /**
     * @return array<string, array{mixed, mixed, bool}>
     */
    public static function nonCastDateCases(): array
    {
        return [
            // updated_at is a date attribute via getDates() but is NOT in $casts.
            'equivalent format' => ['2025-01-01', '2025-01-01 00:00:00', false],
            'same' => ['2025-01-01 12:00:00', '2025-01-01 12:00:00', false],
            'changed' => ['2025-01-01 12:00:00', '2025-01-01 13:00:00', true],
            'null/null' => [null, null, false],
        ];
    }

    #[DataProvider('nonCastDateCases')]
    public function testNonCastDateAttributeDirtyEquivalence($original, $set, bool $expectedDirty)
    {
        $model = (new CastEquivalenceTimestampModel)->newFromBuilder(['updated_at' => $original]);

        $attributes = $model->getAttributes();
        $attributes['updated_at'] = $set;
        $model->setRawAttributes($attributes, false);

        $this->assertSame($expectedDirty, $model->isDirty('updated_at'));
        $this->assertSame(! $expectedDirty, $model->originalIsEquivalent('updated_at'));
    }

    public function testGetCastTypeOverrideIsHonoredOnTheCastPath()
    {
        // The attribute is declared as a string cast, but the model overrides
        // getCastType() to force it to boolean. Casting must follow the override,
        // not the raw declaration — guards against resolving the cast type
        // without consulting getCastType().
        $model = (new CastTypeOverrideModel)->newFromBuilder(['flag' => '1']);

        $this->assertTrue($model->flag);

        $model->flag = '0';

        $this->assertFalse($model->flag);
    }

    public function testBuiltInCastObjectsAreSharedFlyweights()
    {
        $resolve = fn ($model, $cast) => \Closure::bind(
            fn ($key) => $this->getInternalCastClass($key),
            (new CastEquivalenceModel([], ['value' => $cast]))->newFromBuilder(['value' => null]),
            Model::class,
        )('value');

        // Aliases collapse to a single shared instance...
        $float = $resolve(null, 'float');
        $this->assertSame($float, $resolve(null, 'double'));
        $this->assertSame($float, $resolve(null, 'real'));

        // ...as does every enum, regardless of the concrete enum class...
        $enum = $resolve(null, CastEquivalenceIntEnum::class);
        $this->assertSame($enum, $resolve(null, CastEquivalenceStringEnum::class));

        // ...and every custom cast class.
        $class = $resolve(null, CastEquivalenceReverseCast::class);
        $this->assertSame($class, $resolve(null, CastEquivalenceReverseCast::class));

        // Different logical types remain distinct.
        $this->assertNotSame($float, $enum);
        $this->assertNotSame($float, $class);
    }
}

enum CastEquivalenceIntEnum: int
{
    case A = 1;
    case B = 2;
}

enum CastEquivalenceStringEnum: string
{
    case A = 'a';
    case B = 'b';
}

class CastEquivalenceReverseCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return is_null($value) ? null : strrev((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return is_null($value) ? null : strrev((string) $value);
    }
}

class CastEquivalenceModel extends Model
{
    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s';

    public $timestamps = false;

    public function __construct(array $attributes = [], array $casts = [])
    {
        $this->casts = $casts;

        parent::__construct($attributes);
    }
}

class CastEquivalenceTimestampModel extends Model
{
    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s';

    public $timestamps = true;

    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }
}

class CastTypeOverrideModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['flag' => 'string'];

    protected function getCastType($key)
    {
        return $key === 'flag' ? 'boolean' : parent::getCastType($key);
    }
}
