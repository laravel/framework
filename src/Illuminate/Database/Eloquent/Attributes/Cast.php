<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use BackedEnum;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException as BrickMathException;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Contracts\AttributesContract;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Exceptions\InvalidArgumentException;
use ReflectionException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Cast implements AttributesContract
{
    protected string $castType;
    protected Model $model;
    protected string $key;

    public function __construct(?string $castType = null)
    {
        $this->validateCastType($castType);
        $this->castType = $castType;
    }

    protected function validateCastType(string $castType): void
    {
        $allowedCastTypes = [
            'array',
            'bool',
            'boolean',
            'collection',
            'custom_datetime',
            'date',
            'datetime',
            'decimal',
            'double',
            'encrypted',
            'encrypted:array',
            'encrypted:collection',
            'encrypted:json',
            'encrypted:object',
            'float',
            'hashed',
            'immutable_date',
            'immutable_datetime',
            'immutable_custom_datetime',
            'int',
            'integer',
            'json',
            'object',
            'real',
            'string',
            'timestamp',
        ];

        if (!in_array($castType, $allowedCastTypes)) {
            throw new \InvalidArgumentException("Invalid cast type: $castType");
        }
    }

    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getCastedValue(mixed $value): mixed
    {
        if ($this->isEncryptedCastable()) {
            $value = $this->fromEncryptedString($value);

            $this->castType = Str::after($this->castType, 'encrypted:');
        }
        if ($this->isEnumCastable($this->key)) {
            return $this->getEnumCastableAttributeValue($value);
        }

        if ($this->isClassCastable()) {
            return $this->getClassCastableAttributeValue($value);
        }

        return match ($this->castType) {
            'int', 'integer' => (int)$value,
            'real', 'float', 'double' => $this->fromFloat($value),
            'string' => (string)$value,
            'bool', 'boolean' => (bool)$value,
            'object' => $this->fromJson($value, true),
            'array', 'json' => $this->fromJson($value),
            'collection' => new BaseCollection($this->fromJson($value)),
            'date' => $this->asDate($value),
            'datetime', 'custom_datetime' => $this->asDateTime($value),
            'immutable_date' => $this->asDate($value)->toImmutable(),
            'immutable_custom_datetime', 'immutable_datetime' => $this->asDateTime($value)->toImmutable(),
            'timestamp' => $this->asTimestamp($value),
            // TODO: fix decimal
            default => $value
        };
    }

    protected function isEncryptedCastable(): bool
    {
        return in_array(['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object'],
            $this->key);
    }

    public function fromEncryptedString($value)
    {
        return ($this->model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($value, false);
    }

    protected function isEnumCastable(): bool
    {

        if (in_array($this->castType, $this->model::$primitiveCastTypes)) {
            return false;
        }

        return enum_exists($this->castType);
    }

    protected function getEnumCastableAttributeValue($value)
    {
        if (is_null($value)) {
            return;
        }

        if ($value instanceof $this->castType) {
            return $value;
        }

        return $this->getEnumCaseFromValue($this->castType, $value);
    }

    protected function getEnumCaseFromValue($enumClass, $value)
    {
        return is_subclass_of($enumClass, BackedEnum::class)
            ? $enumClass::from($value)
            : constant($enumClass . '::' . $value);
    }

    protected function isClassCastable(): bool
    {
        $castType = $this->parseCasterClass($this->castType);

        if (in_array($castType, $this->model::$primitiveCastTypes)) {
            return false;
        }

        if (class_exists($castType)) {
            return true;
        }

        throw new InvalidCastException($this->model->getModel(), $this->key, $castType);
    }

    protected function parseCasterClass($class): string
    {
        return !str_contains($class, ':')
            ? $class
            : explode(':', $class, 2)[0];
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function castAttribute($value): mixed
    {
        $key = $this->key;
        if($this->model->hasAttribute($key, Cast::class, 'property')){
            $instance = $this->model->getAttributeInstance($key, Cast::class, 'property');
            if($instance instanceof Cast){
                $instance->setKey($key);
                $instance->setModel($this);

                return $instance->getCastedValue($value);
            }
        }

        $castType = $this->getCastType($key);

        if (is_null($value)) {
            return null;
        }

        $caster = new Cast($castType);
        $caster->setKey($key);
        $caster->setModel($this);

        return $caster->getCastedValue($value);
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param string $key
     *
     * @return string
     * @throws ReflectionException
     */
    protected function getCastType(): string
    {
        $key = $this->key;
        $castType = $this->model->getCasts()[$key];

        if (isset($this->model::$castTypeCache[$castType])) {
            return $this->model::$castTypeCache[$castType];
        }

        if ($this->isCustomDateTimeCast($castType)) {
            $convertedCastType = 'custom_datetime';
        } elseif ($this->isImmutableCustomDateTimeCast($castType)) {
            $convertedCastType = 'immutable_custom_datetime';
        } elseif ($this->isDecimalCast($castType)) {
            $convertedCastType = 'decimal';
        } elseif (class_exists($castType)) {
            $convertedCastType = $castType;
        } else {
            $convertedCastType = trim(strtolower($castType));
        }

        return static::$castTypeCache[$castType] = $convertedCastType;
    }


    /**
     * Determine if the key is serializable using a custom class.
     *
     * @param  string  $key
     *
     * @return bool
     * @throws InvalidCastException
     */
    protected function isClassSerializable()
    {
        $key = $this->key;
        return ! $this->isEnumCastable($key) &&
            $this->isClassCastable($key) &&
            method_exists($this->resolveCasterClass(), 'serialize');
    }


    protected function getClassCastableAttributeValue($value)
    {
        $key = $this->key;

        $caster = $this->resolveCasterClass();

        $objectCachingDisabled = $caster->withoutObjectCaching ?? false;

        if (isset($this->classCastCache[$key]) && !$objectCachingDisabled) {
            return $this->classCastCache[$key];
        } else {
            $value = $caster instanceof CastsInboundAttributes
                ? $value
                : $caster->get($this, $key, $value, $this->model->attributes);

            if ($caster instanceof CastsInboundAttributes ||
                !is_object($value) ||
                $objectCachingDisabled) {
                unset($this->classCastCache[$key]);
            } else {
                $this->classCastCache[$key] = $value;
            }

            return $value;
        }
    }

    /**
     * Serialize the given attribute using the custom cast class.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function serializeClassCastableAttribute($value)
    {
        $caster = $this->getCasterInstance($this->key);
        return $caster->serialize(
            $this, $this->key, $value, $this->model->attributes
        );
    }

    protected function resolveCasterClass()
    {
        $castType = $this->castType;

        $arguments = [];

        if (str_contains($castType, ':')) {
            $segments = explode(':', $castType, 2);

            $castType = $segments[0];
            $arguments = explode(',', $segments[1]);
        }

        if (is_subclass_of($castType, Castable::class)) {
            $castType = $castType::castUsing($arguments);
        }

        if (is_object($castType)) {
            return $castType;
        }

        return new $castType(...$arguments);
    }

    public function fromFloat($value): float
    {
        return match ((string)$value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float)$value,
        };
    }

    public function fromJson($value, $asObject = false)
    {
        return Json::decode($value ?? '', !$asObject);
    }

    protected function asDate($value): Carbon
    {
        return $this->asDateTime($value)->startOfDay();
    }

    protected function asDateTime($value): bool|Carbon
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = $this->getDateFormat();

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Date::createFromFormat($format, $value);
        } catch (InvalidArgumentException) {
            $date = false;
        }

        return $date ?: Date::parse($value);
    }

    protected function isStandardDateFormat($value): bool|int
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    public function getDateFormat(): string
    {

        return $this->model->dateFormat ?: $this->model->getConnection()->getQueryGrammar()->getDateFormat();
    }

    protected function asTimestamp($value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Cast the given attribute to a hashed string.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function castAttributeAsHashedString(mixed $value): string
    {
        return $value !== null && !Hash::isHashed($value) ? Hash::make($value) : $value;
    }

    public function mutateAttributeMarkedAttribute($value)
    {
        if (array_key_exists($this->key, $this->model->attributeCastCache)) {
            return $this->model->attributeCastCache[$this->key];
        }

        $attribute = $this->model->{Str::camel($this->key)}();

        $value = call_user_func($attribute->get ?: function ($value) {
            return $value;
        }, $value, $this->model->getAttributes());

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->model->attributeCastCache[$this->key] = $value;
        } else {
            unset($this->model->attributeCastCache[$this->key]);
        }

        return $value;
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public function fromDateTime($value): ?string
    {
        return empty($value) ? $value : $this->asDateTime($value)->format(
            $this->getDateFormat()
        );
    }

    /**
     * Cast the given attribute to an encrypted string.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return string
     */
    protected function castAttributeAsEncryptedString($value)
    {
        return (static::$encrypter ?? Crypt::getFacadeRoot())->encrypt($value, false);
    }

    /**
     * Set the value of a class castable attribute.
     *
     * @param mixed $value
     *
     * @return void
     */
    protected function setClassCastableAttribute($value): void
    {
        $caster = $this->resolveCasterClass();

        $this->model->attributes = array_replace(
            $this->model->attributes,
            $this->normalizeCastClassResponse($caster->set(
                $this, $this->key, $value, $this->model->attributes
            ))
        );

        if ($caster instanceof CastsInboundAttributes ||
            !is_object($value) ||
            ($caster->model->withoutObjectCaching ?? false)) {
            unset($this->mode->classCastCache[$this->key]);
        } else {
            $this->model->classCastCache[$this->key] = $value;
        }
    }

    /**
     * Normalize the response from a custom class caster.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return array
     */
    protected function normalizeCastClassResponse($value)
    {
        return is_array($value) ? $value : [$this->key => $value];
    }

    /**
     * Cast the given attribute to JSON.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return string
     */
    protected function castAttributeAsJson(mixed $value): string
    {
        $value = $this->asJson($value);

        if ($value === false) {
            throw JsonEncodingException::forAttribute(
                $this, $this->key, json_last_error_msg()
            );
        }

        return $value;
    }

    /**
     * Encode the given value as JSON.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function asJson($value): string
    {
        return Json::encode($value);
    }

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isDateAttribute(): bool
    {
        return in_array($this->key, $this->model->getDates(), true) ||
            $this->isDateCastable($this->key);
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
     *
     * @param string $key
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function isDateCastable(): bool
    {
        return $this->model->hasCast($this->key, ['date', 'datetime', 'immutable_date', 'immutable_datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param string $key
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function isJsonCastable(): bool
    {
        return $this->model->hasCast($this->key, [
            'array',
            'json',
            'object',
            'collection',
            'encrypted:array',
            'encrypted:collection',
            'encrypted:json',
            'encrypted:object',
        ]);
    }

    /**
     * Determine whether a value is Date / DateTime custom-castable for inbound manipulation.
     *
     * @param string $key
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function isDateCastableWithCustomFormat(): bool
    {
        return $this->model->hasCast($this->key, ['custom_datetime', 'immutable_custom_datetime']);
    }

    protected function asDecimal($value, $decimals): string
    {
        try {
            return (string)BigDecimal::of($value)->toScale($decimals, RoundingMode::HALF_UP);
        } catch (BrickMathException $e) {
            throw new MathException('Unable to cast value to a decimal.', previous: $e);
        }
    }
}