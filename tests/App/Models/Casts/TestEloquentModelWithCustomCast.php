<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Tests\App\Casts\AddressCaster;
use Illuminate\Tests\App\Casts\DateObjectCaster;
use Illuminate\Tests\App\Casts\DateTimezoneCasterWithObjectCaching;
use Illuminate\Tests\App\Casts\DateTimezoneCasterWithoutObjectCaching;
use Illuminate\Tests\App\Casts\DecimalCaster;
use Illuminate\Tests\App\Casts\DOBCaster;
use Illuminate\Tests\App\Casts\HashCaster;
use Illuminate\Tests\App\Casts\JsonCaster;
use Illuminate\Tests\App\Casts\JsonSettingsCaster;
use Illuminate\Tests\App\Casts\UppercaseCaster;
use Illuminate\Tests\App\ValueObjects\AddressCastValue;
use Illuminate\Tests\App\ValueObjects\ValueObject;
use Illuminate\Tests\App\ValueObjects\ValueObjectWithCasterInstance;
use RuntimeException;

class TestEloquentModelWithCustomCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'dob' => DOBCaster::class,
        'address' => AddressCaster::class,
        'price' => DecimalCaster::class,
        'password' => HashCaster::class,
        'other_password' => HashCaster::class.':md5',
        'uppercase' => UppercaseCaster::class,
        'options' => JsonCaster::class,
        'typed_settings' => JsonSettingsCaster::class,
        'value_object_with_caster' => ValueObject::class,
        'value_object_caster_with_argument' => ValueObject::class.':argument',
        'value_object_caster_with_caster_instance' => ValueObjectWithCasterInstance::class,
        'undefined_cast_column' => UndefinedCast::class,
        'birthday_at' => DateObjectCaster::class,
        'anniversary_on_with_object_caching' => DateTimezoneCasterWithObjectCaching::class.':America/New_York',
        'anniversary_on_without_object_caching' => DateTimezoneCasterWithoutObjectCaching::class.':America/New_York',
    ];

    protected function getTobAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (isset($this->attributes['dob'])) {
            return Carbon::parse($this->attributes['dob'])->toDateString().' '.
                Carbon::parse($value)->toTimeString();
        }

        return Carbon::parse($value)->toDateTimeString();
    }

    /**
     * A computed attribute that depends on another casted attribute.
     *
     * This simulates a mutator that uses the value of a casted property.
     */
    protected function addressString(): Attribute
    {
        return Attribute::get(function () {
            $address = $this->address;

            // If mergeAttributesFromClassCasts() hasn't prepared casts properly,
            // this could be an array instead of an Address instance.
            if (! $address instanceof AddressCastValue) {
                throw new RuntimeException('Address was not cast before mutator access.');
            }

            return "{$address->lineOne} ({$address->lineTwo})";
        });
    }
}
