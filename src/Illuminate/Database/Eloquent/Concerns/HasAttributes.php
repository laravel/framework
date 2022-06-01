<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

trait HasAttributes
{
    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * The changed model attributes.
     *
     * @var array
     */
    protected $changes = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that have been cast using custom classes.
     *
     * @var array
     */
    protected $classCastCache = [];

    /**
     * The attributes that have been cast using "Attribute" return type mutators.
     *
     * @var array
     */
    protected $attributeCastCache = [];

    /**
     * The built-in, primitive cast types supported by Eloquent.
     *
     * @var string[]
     */
    protected static $primitiveCastTypes = [
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

    /**
     * The attributes that should be mutated to dates.
     *
     * @deprecated Use the "casts" property
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The cache of the mutated attributes for each class.
     *
     * @var array
     */
    protected static $mutatorCache = [];

    /**
     * The cache of the "Attribute" return type marked mutated attributes for each class.
     *
     * @var array
     */
    protected static $attributeMutatorCache = [];

    /**
     * The cache of the "Attribute" return type marked mutated, gettable attributes for each class.
     *
     * @var array
     */
    protected static $getAttributeMutatorCache = [];

    /**
     * The cache of the "Attribute" return type marked mutated, settable attributes for each class.
     *
     * @var array
     */
    protected static $setAttributeMutatorCache = [];

    /**
     * The encrypter instance that is used to encrypt attributes.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    public static $encrypter;

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * Add the date attributes to the attributes array.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function addDateAttributesToArray(array $attributes)
    {
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        return $attributes;
    }

    /**
     * Add the mutated attributes to the attributes array.
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addMutatedAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        foreach ($mutatedAttributes as $key) {
            // We want to spin through all the mutated attributes for this model and call
            // the mutator for the attribute. We cache off every mutated attributes so
            // we don't have to constantly check on attributes that actually change.
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            // Next, we will call the mutator for this attribute so that we can get these
            // mutated attribute's actual values. After we finish mutating each of the
            // attributes we will return this final array of the mutated attributes.
            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }

        return $attributes;
    }

    /**
     * Add the casted attributes to the attributes array.
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addCastAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        foreach ($this->getCasts() as $key => $value) {
            if (! array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)) {
                continue;
            }

            // Here we will cast the attribute. Then, if the cast is a date or datetime cast
            // then we will serialize the date for the array. This will convert the dates
            // to strings based on the date format specified for these Eloquent models.
            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );

            // If the attribute cast was a date or a datetime, we will serialize the date as
            // a string. This allows the developers to customize how dates are serialized
            // into an array without affecting how they are persisted into the storage.
            if ($attributes[$key] && in_array($value, ['date', 'datetime', 'immutable_date', 'immutable_datetime'])) {
                $attributes[$key] = $this->serializeDate($attributes[$key]);
            }

            if ($attributes[$key] && ($this->isCustomDateTimeCast($value) ||
                $this->isImmutableCustomDateTimeCast($value))) {
                $attributes[$key] = $attributes[$key]->format(explode(':', $value, 2)[1]);
            }

            if ($attributes[$key] instanceof DateTimeInterface &&
                $this->isClassCastable($key)) {
                $attributes[$key] = $this->serializeDate($attributes[$key]);
            }

            if ($attributes[$key] && $this->isClassSerializable($key)) {
                $attributes[$key] = $this->serializeClassCastableAttribute($key, $attributes[$key]);
            }

            if ($this->isEnumCastable($key) && (! ($attributes[$key] ?? null) instanceof Arrayable)) {
                $attributes[$key] = isset($attributes[$key]) ? $attributes[$key]->value : null;
            }

            if ($attributes[$key] instanceof Arrayable) {
                $attributes[$key] = $attributes[$key]->toArray();
            }
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->getArrayableItems($this->getAttributes());
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }

        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implement the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes, since null is used to represent empty relationships if
            // it has a has one or belongs to type relationships on the models.
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
            if (static::$snakeAttributes) {
                $key = Str::snake($key);
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable relations.
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! $key) {
            return;
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        if (array_key_exists($key, $this->attributes) ||
            array_key_exists($key, $this->casts) ||
            $this->hasGetMutator($key) ||
            $this->hasAttributeMutator($key) ||
            $this->isClassCastable($key)) {
            return $this->getAttributeValue($key);
        }

        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        if (method_exists(self::class, $key)) {
            return;
        }

        return $this->getRelationValue($key);
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        return $this->transformModelValue($key, $this->getAttributeFromArray($key));
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        return $this->getAttributes()[$key] ?? null;
    }

    /**
     * Get a relationship.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if (! $this->isRelation($key)) {
            return;
        }

        if ($this->preventsLazyLoading) {
            $this->handleLazyLoadingViolation($key);
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        return $this->getRelationshipFromMethod($key);
    }

    /**
     * Determine if the given key is a relationship method on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function isRelation($key)
    {
        if ($this->hasAttributeMutator($key)) {
            return false;
        }

        return method_exists($this, $key) ||
            (static::$relationResolvers[get_class($this)][$key] ?? null);
    }

    /**
     * Handle a lazy loading violation.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function handleLazyLoadingViolation($key)
    {
        if (isset(static::$lazyLoadingViolationCallback)) {
            return call_user_func(static::$lazyLoadingViolationCallback, $this, $key);
        }

        if (! $this->exists || $this->wasRecentlyCreated) {
            return;
        }

        throw new LazyLoadingViolationException($this, $key);
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        $relation = $this->$method();

        if (! $relation instanceof Relation) {
            if (is_null($relation)) {
                throw new LogicException(sprintf(
                    '%s::%s must return a relationship instance, but "null" was returned. Was the "return" keyword used?', static::class, $method
                ));
            }

            throw new LogicException(sprintf(
                '%s::%s must return a relationship instance.', static::class, $method
            ));
        }

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    /**
     * Determine if a "Attribute" return type marked mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasAttributeMutator($key)
    {
        if (isset(static::$attributeMutatorCache[get_class($this)][$key])) {
            return static::$attributeMutatorCache[get_class($this)][$key];
        }

        if (! method_exists($this, $method = Str::camel($key))) {
            return static::$attributeMutatorCache[get_class($this)][$key] = false;
        }

        $returnType = (new ReflectionMethod($this, $method))->getReturnType();

        return static::$attributeMutatorCache[get_class($this)][$key] =
                    $returnType instanceof ReflectionNamedType &&
                    $returnType->getName() === Attribute::class;
    }

    /**
     * Determine if a "Attribute" return type marked get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasAttributeGetMutator($key)
    {
        if (isset(static::$getAttributeMutatorCache[get_class($this)][$key])) {
            return static::$getAttributeMutatorCache[get_class($this)][$key];
        }

        if (! $this->hasAttributeMutator($key)) {
            return static::$getAttributeMutatorCache[get_class($this)][$key] = false;
        }

        return static::$getAttributeMutatorCache[get_class($this)][$key] = is_callable($this->{Str::camel($key)}()->get);
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * Get the value of an "Attribute" return type marked attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttributeMarkedAttribute($key, $value)
    {
        if (array_key_exists($key, $this->attributeCastCache)) {
            return $this->attributeCastCache[$key];
        }

        $attribute = $this->{Str::camel($key)}();

        $value = call_user_func($attribute->get ?: function ($value) {
            return $value;
        }, $value, $this->attributes);

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->attributeCastCache[$key] = $value;
        } else {
            unset($this->attributeCastCache[$key]);
        }

        return $value;
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        if ($this->isClassCastable($key)) {
            $value = $this->getClassCastableAttributeValue($key, $value);
        } elseif (isset(static::$getAttributeMutatorCache[get_class($this)][$key]) &&
                  static::$getAttributeMutatorCache[get_class($this)][$key] === true) {
            $value = $this->mutateAttributeMarkedAttribute($key, $value);

            $value = $value instanceof DateTimeInterface
                        ? $this->serializeDate($value)
                        : $value;
        } else {
            $value = $this->mutateAttribute($key, $value);
        }

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Merge new casts with existing casts on the model.
     *
     * @param  array  $casts
     * @return $this
     */
    public function mergeCasts($casts)
    {
        $this->casts = array_merge($this->casts, $casts);

        return $this;
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        $castType = $this->getCastType($key);

        if (is_null($value) && in_array($castType, static::$primitiveCastTypes)) {
            return $value;
        }

        // If the key is one of the encrypted castable types, we'll first decrypt
        // the value and update the cast type so we may leverage the following
        // logic for casting this value to any additionally specified types.
        if ($this->isEncryptedCastable($key)) {
            $value = $this->fromEncryptedString($value);

            $castType = Str::after($castType, 'encrypted:');
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return $this->fromFloat($value);
            case 'decimal':
                return $this->asDecimal($value, explode(':', $this->getCasts()[$key], 2)[1]);
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new BaseCollection($this->fromJson($value));
            case 'date':
                return $this->asDate($value);
            case 'datetime':
            case 'custom_datetime':
                return $this->asDateTime($value);
            case 'immutable_date':
                return $this->asDate($value)->toImmutable();
            case 'immutable_custom_datetime':
            case 'immutable_datetime':
                return $this->asDateTime($value)->toImmutable();
            case 'timestamp':
                return $this->asTimestamp($value);
        }

        if ($this->isEnumCastable($key)) {
            return $this->getEnumCastableAttributeValue($key, $value);
        }

        if ($this->isClassCastable($key)) {
            return $this->getClassCastableAttributeValue($key, $value);
        }

        return $value;
    }

    /**
     * Cast the given attribute using a custom cast class.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getClassCastableAttributeValue($key, $value)
    {
        if (isset($this->classCastCache[$key])) {
            return $this->classCastCache[$key];
        } else {
            $caster = $this->resolveCasterClass($key);

            $value = $caster instanceof CastsInboundAttributes
                ? $value
                : $caster->get($this, $key, $value, $this->attributes);

            if ($caster instanceof CastsInboundAttributes || ! is_object($value)) {
                unset($this->classCastCache[$key]);
            } else {
                $this->classCastCache[$key] = $value;
            }

            return $value;
        }
    }

    /**
     * Cast the given attribute to an enum.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getEnumCastableAttributeValue($key, $value)
    {
        if (is_null($value)) {
            return;
        }

        $castType = $this->getCasts()[$key];

        if ($value instanceof $castType) {
            return $value;
        }

        return $castType::from($value);
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param  string  $key
     * @return string
     */
    protected function getCastType($key)
    {
        if ($this->isCustomDateTimeCast($this->getCasts()[$key])) {
            return 'custom_datetime';
        }

        if ($this->isImmutableCustomDateTimeCast($this->getCasts()[$key])) {
            return 'immutable_custom_datetime';
        }

        if ($this->isDecimalCast($this->getCasts()[$key])) {
            return 'decimal';
        }

        return trim(strtolower($this->getCasts()[$key]));
    }

    /**
     * Increment or decrement the given attribute using the custom cast class.
     *
     * @param  string  $method
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function deviateClassCastableAttribute($method, $key, $value)
    {
        return $this->resolveCasterClass($key)->{$method}(
            $this, $key, $value, $this->attributes
        );
    }

    /**
     * Serialize the given attribute using the custom cast class.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function serializeClassCastableAttribute($key, $value)
    {
        return $this->resolveCasterClass($key)->serialize(
            $this, $key, $value, $this->attributes
        );
    }

    /**
     * Determine if the cast type is a custom date time cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isCustomDateTimeCast($cast)
    {
        return str_starts_with($cast, 'date:') ||
                str_starts_with($cast, 'datetime:');
    }

    /**
     * Determine if the cast type is an immutable custom date time cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isImmutableCustomDateTimeCast($cast)
    {
        return strncmp($cast, 'immutable_date:', 15) === 0 ||
               strncmp($cast, 'immutable_datetime:', 19) === 0;
    }

    /**
     * Determine if the cast type is a decimal cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected function isDecimalCast($cast)
    {
        return str_starts_with($cast, 'decimal:');
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // this model, such as "json_encoding" a listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        } elseif ($this->hasAttributeSetMutator($key)) {
            return $this->setAttributeMarkedMutatedAttributeValue($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isEnumCastable($key)) {
            $this->setEnumCastableAttribute($key, $value);

            return $this;
        }

        if ($this->isClassCastable($key)) {
            $this->setClassCastableAttribute($key, $value);

            return $this;
        }

        if (! is_null($value) && $this->isJsonCastable($key)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        if (str_contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        if (! is_null($value) && $this->isEncryptedCastable($key)) {
            $value = $this->castAttributeAsEncryptedString($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Determine if a set mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
    }

    /**
     * Determine if an "Attribute" return type marked set mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasAttributeSetMutator($key)
    {
        $class = get_class($this);

        if (isset(static::$setAttributeMutatorCache[$class][$key])) {
            return static::$setAttributeMutatorCache[$class][$key];
        }

        if (! method_exists($this, $method = Str::camel($key))) {
            return static::$setAttributeMutatorCache[$class][$key] = false;
        }

        $returnType = (new ReflectionMethod($this, $method))->getReturnType();

        return static::$setAttributeMutatorCache[$class][$key] =
                    $returnType instanceof ReflectionNamedType &&
                    $returnType->getName() === Attribute::class &&
                    is_callable($this->{$method}()->set);
    }

    /**
     * Set the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function setMutatedAttributeValue($key, $value)
    {
        return $this->{'set'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * Set the value of a "Attribute" return type marked attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function setAttributeMarkedMutatedAttributeValue($key, $value)
    {
        $attribute = $this->{Str::camel($key)}();

        $callback = $attribute->set ?: function ($value) use ($key) {
            $this->attributes[$key] = $value;
        };

        $this->attributes = array_merge(
            $this->attributes,
            $this->normalizeCastClassResponse(
                $key, $callback($value, $this->attributes)
            )
        );

        if ($attribute->withCaching || (is_object($value) && $attribute->withObjectCaching)) {
            $this->attributeCastCache[$key] = $value;
        } else {
            unset($this->attributeCastCache[$key]);
        }
    }

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateAttribute($key)
    {
        return in_array($key, $this->getDates(), true) ||
            $this->isDateCastable($key);
    }

    /**
     * Set a given JSON attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function fillJsonAttribute($key, $value)
    {
        [$key, $path] = explode('->', $key, 2);

        $value = $this->asJson($this->getArrayAttributeWithValue(
            $path, $key, $value
        ));

        $this->attributes[$key] = $this->isEncryptedCastable($key)
            ? $this->castAttributeAsEncryptedString($key, $value)
            : $value;

        return $this;
    }

    /**
     * Set the value of a class castable attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    protected function setClassCastableAttribute($key, $value)
    {
        $caster = $this->resolveCasterClass($key);

        $this->attributes = array_merge(
            $this->attributes,
            $this->normalizeCastClassResponse($key, $caster->set(
                $this, $key, $value, $this->attributes
            ))
        );

        if ($caster instanceof CastsInboundAttributes || ! is_object($value)) {
            unset($this->classCastCache[$key]);
        } else {
            $this->classCastCache[$key] = $value;
        }
    }

    /**
     * Set the value of an enum castable attribute.
     *
     * @param  string  $key
     * @param  \BackedEnum  $value
     * @return void
     */
    protected function setEnumCastableAttribute($key, $value)
    {
        $enumClass = $this->getCasts()[$key];

        if (! isset($value)) {
            $this->attributes[$key] = null;
        } elseif ($value instanceof $enumClass) {
            $this->attributes[$key] = $value->value;
        } else {
            $this->attributes[$key] = $enumClass::from($value)->value;
        }
    }

    /**
     * Get an array attribute with the given key and value set.
     *
     * @param  string  $path
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    protected function getArrayAttributeWithValue($path, $key, $value)
    {
        return tap($this->getArrayAttributeByKey($key), function (&$array) use ($path, $value) {
            Arr::set($array, str_replace('->', '.', $path), $value);
        });
    }

    /**
     * Get an array attribute or return an empty array if it is not set.
     *
     * @param  string  $key
     * @return array
     */
    protected function getArrayAttributeByKey($key)
    {
        if (! isset($this->attributes[$key])) {
            return [];
        }

        return $this->fromJson(
            $this->isEncryptedCastable($key)
                ? $this->fromEncryptedString($this->attributes[$key])
                : $this->attributes[$key]
        );
    }

    /**
     * Cast the given attribute to JSON.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function castAttributeAsJson($key, $value)
    {
        $value = $this->asJson($value);

        if ($value === false) {
            throw JsonEncodingException::forAttribute(
                $this, $key, json_last_error_msg()
            );
        }

        return $value;
    }

    /**
     * Encode the given value as JSON.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string  $value
     * @param  bool  $asObject
     * @return mixed
     */
    public function fromJson($value, $asObject = false)
    {
        return json_decode($value, ! $asObject);
    }

    /**
     * Decrypt the given encrypted string.
     *
     * @param  string  $value
     * @return mixed
     */
    public function fromEncryptedString($value)
    {
        return (static::$encrypter ?? Crypt::getFacadeRoot())->decrypt($value, false);
    }

    /**
     * Cast the given attribute to an encrypted string.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function castAttributeAsEncryptedString($key, $value)
    {
        return (static::$encrypter ?? Crypt::getFacadeRoot())->encrypt($value, false);
    }

    /**
     * Set the encrypter instance that will be used to encrypt attributes.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public static function encryptUsing($encrypter)
    {
        static::$encrypter = $encrypter;
    }

    /**
     * Decode the given float.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function fromFloat($value)
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    /**
     * Return a decimal as string.
     *
     * @param  float  $value
     * @param  int  $decimals
     * @return string
     */
    protected function asDecimal($value, $decimals)
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDate($value)
    {
        return $this->asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDateTime($value)
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
        } catch (InvalidArgumentException $e) {
            $date = false;
        }

        return $date ?: Date::parse($value);
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function fromDateTime($value)
    {
        return empty($value) ? $value : $this->asDateTime($value)->format(
            $this->getDateFormat()
        );
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp($value)
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date instanceof DateTimeImmutable ?
            CarbonImmutable::instance($date)->toJSON() :
            Carbon::instance($date)->toJSON();
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        if (! $this->usesTimestamps()) {
            return $this->dates;
        }

        $defaults = [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        return array_unique(array_merge($this->dates, $defaults));
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Set the date format used by the model.
     *
     * @param  string  $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        if ($this->getIncrementing()) {
            return array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts);
        }

        return $this->casts;
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateCastable($key)
    {
        return $this->hasCast($key, ['date', 'datetime', 'immutable_date', 'immutable_datetime']);
    }

    /**
     * Determine whether a value is Date / DateTime custom-castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateCastableWithCustomFormat($key)
    {
        return $this->hasCast($key, ['custom_datetime', 'immutable_custom_datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object']);
    }

    /**
     * Determine whether a value is an encrypted castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isEncryptedCastable($key)
    {
        return $this->hasCast($key, ['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object']);
    }

    /**
     * Determine if the given key is cast using a custom class.
     *
     * @param  string  $key
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\InvalidCastException
     */
    protected function isClassCastable($key)
    {
        if (! array_key_exists($key, $this->getCasts())) {
            return false;
        }

        $castType = $this->parseCasterClass($this->getCasts()[$key]);

        if (in_array($castType, static::$primitiveCastTypes)) {
            return false;
        }

        if (class_exists($castType)) {
            return true;
        }

        throw new InvalidCastException($this->getModel(), $key, $castType);
    }

    /**
     * Determine if the given key is cast using an enum.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isEnumCastable($key)
    {
        if (! array_key_exists($key, $this->getCasts())) {
            return false;
        }

        $castType = $this->getCasts()[$key];

        if (in_array($castType, static::$primitiveCastTypes)) {
            return false;
        }

        if (function_exists('enum_exists') && enum_exists($castType)) {
            return true;
        }
    }

    /**
     * Determine if the key is deviable using a custom class.
     *
     * @param  string  $key
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\InvalidCastException
     */
    protected function isClassDeviable($key)
    {
        return $this->isClassCastable($key) &&
            method_exists($castType = $this->parseCasterClass($this->getCasts()[$key]), 'increment') &&
            method_exists($castType, 'decrement');
    }

    /**
     * Determine if the key is serializable using a custom class.
     *
     * @param  string  $key
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\InvalidCastException
     */
    protected function isClassSerializable($key)
    {
        return ! $this->isEnumCastable($key) &&
            $this->isClassCastable($key) &&
            method_exists($this->resolveCasterClass($key), 'serialize');
    }

    /**
     * Resolve the custom caster class for a given key.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function resolveCasterClass($key)
    {
        $castType = $this->getCasts()[$key];

        $arguments = [];

        if (is_string($castType) && str_contains($castType, ':')) {
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

    /**
     * Parse the given caster class, removing any arguments.
     *
     * @param  string  $class
     * @return string
     */
    protected function parseCasterClass($class)
    {
        return ! str_contains($class, ':')
            ? $class
            : explode(':', $class, 2)[0];
    }

    /**
     * Merge the cast class and attribute cast attributes back into the model.
     *
     * @return void
     */
    protected function mergeAttributesFromCachedCasts()
    {
        $this->mergeAttributesFromClassCasts();
        $this->mergeAttributesFromAttributeCasts();
    }

    /**
     * Merge the cast class attributes back into the model.
     *
     * @return void
     */
    protected function mergeAttributesFromClassCasts()
    {
        foreach ($this->classCastCache as $key => $value) {
            $caster = $this->resolveCasterClass($key);

            $this->attributes = array_merge(
                $this->attributes,
                $caster instanceof CastsInboundAttributes
                    ? [$key => $value]
                    : $this->normalizeCastClassResponse($key, $caster->set($this, $key, $value, $this->attributes))
            );
        }
    }

    /**
     * Merge the cast class attributes back into the model.
     *
     * @return void
     */
    protected function mergeAttributesFromAttributeCasts()
    {
        foreach ($this->attributeCastCache as $key => $value) {
            $attribute = $this->{Str::camel($key)}();

            if ($attribute->get && ! $attribute->set) {
                continue;
            }

            $callback = $attribute->set ?: function ($value) use ($key) {
                $this->attributes[$key] = $value;
            };

            $this->attributes = array_merge(
                $this->attributes,
                $this->normalizeCastClassResponse(
                    $key, $callback($value, $this->attributes)
                )
            );
        }
    }

    /**
     * Normalize the response from a custom class caster.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    protected function normalizeCastClassResponse($key, $value)
    {
        return is_array($value) ? $value : [$key => $value];
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        $this->mergeAttributesFromCachedCasts();

        return $this->attributes;
    }

    /**
     * Get all of the current attributes on the model for an insert operation.
     *
     * @return array
     */
    protected function getAttributesForInsert()
    {
        return $this->getAttributes();
    }

    /**
     * Set the array of model attributes. No checking is done.
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        $this->classCastCache = [];
        $this->attributeCastCache = [];

        return $this;
    }

    /**
     * Get the model's original attribute values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getOriginal($key = null, $default = null)
    {
        return (new static)->setRawAttributes(
            $this->original, $sync = true
        )->getOriginalWithoutRewindingModel($key, $default);
    }

    /**
     * Get the model's original attribute values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    protected function getOriginalWithoutRewindingModel($key = null, $default = null)
    {
        if ($key) {
            return $this->transformModelValue(
                $key, Arr::get($this->original, $key, $default)
            );
        }

        return collect($this->original)->mapWithKeys(function ($value, $key) {
            return [$key => $this->transformModelValue($key, $value)];
        })->all();
    }

    /**
     * Get the model's raw original attribute values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getRawOriginal($key = null, $default = null)
    {
        return Arr::get($this->original, $key, $default);
    }

    /**
     * Get a subset of the model's attributes.
     *
     * @param  array|mixed  $attributes
     * @return array
     */
    public function only($attributes)
    {
        $results = [];

        foreach (is_array($attributes) ? $attributes : func_get_args() as $attribute) {
            $results[$attribute] = $this->getAttribute($attribute);
        }

        return $results;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->getAttributes();

        return $this;
    }

    /**
     * Sync a single original attribute with its current value.
     *
     * @param  string  $attribute
     * @return $this
     */
    public function syncOriginalAttribute($attribute)
    {
        return $this->syncOriginalAttributes($attribute);
    }

    /**
     * Sync multiple original attribute with their current values.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function syncOriginalAttributes($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $modelAttributes = $this->getAttributes();

        foreach ($attributes as $attribute) {
            $this->original[$attribute] = $modelAttributes[$attribute];
        }

        return $this;
    }

    /**
     * Sync the changed attributes.
     *
     * @return $this
     */
    public function syncChanges()
    {
        $this->changes = $this->getDirty();

        return $this;
    }

    /**
     * Determine if the model or any of the given attribute(s) have been modified.
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        return $this->hasChanges(
            $this->getDirty(), is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Determine if the model or all the given attribute(s) have remained the same.
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isClean($attributes = null)
    {
        return ! $this->isDirty(...func_get_args());
    }

    /**
     * Determine if the model or any of the given attribute(s) have been modified.
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function wasChanged($attributes = null)
    {
        return $this->hasChanges(
            $this->getChanges(), is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Determine if any of the given attributes were changed.
     *
     * @param  array  $changes
     * @param  array|string|null  $attributes
     * @return bool
     */
    protected function hasChanges($changes, $attributes = null)
    {
        // If no specific attributes were provided, we will just see if the dirty array
        // already contains any attributes. If it does we will just return that this
        // count is greater than zero. Else, we need to check specific attributes.
        if (empty($attributes)) {
            return count($changes) > 0;
        }

        // Here we will spin through every attribute and see if this is in the array of
        // dirty attributes. If it is, we will return true and if we make it through
        // all of the attributes for the entire array we will return false at end.
        foreach (Arr::wrap($attributes) as $attribute) {
            if (array_key_exists($attribute, $changes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the attributes that have been changed since the last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (! $this->originalIsEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Get the attributes that were changed.
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Determine if the new and old values for a given key are equivalent.
     *
     * @param  string  $key
     * @return bool
     */
    public function originalIsEquivalent($key)
    {
        if (! array_key_exists($key, $this->original)) {
            return false;
        }

        $attribute = Arr::get($this->attributes, $key);
        $original = Arr::get($this->original, $key);

        if ($attribute === $original) {
            return true;
        } elseif (is_null($attribute)) {
            return false;
        } elseif ($this->isDateAttribute($key) || $this->isDateCastableWithCustomFormat($key)) {
            return $this->fromDateTime($attribute) ===
                $this->fromDateTime($original);
        } elseif ($this->hasCast($key, ['object', 'collection'])) {
            return $this->fromJson($attribute) ===
                $this->fromJson($original);
        } elseif ($this->hasCast($key, ['real', 'float', 'double'])) {
            if ($original === null) {
                return false;
            }

            return abs($this->castAttribute($key, $attribute) - $this->castAttribute($key, $original)) < PHP_FLOAT_EPSILON * 4;
        } elseif ($this->hasCast($key, static::$primitiveCastTypes)) {
            return $this->castAttribute($key, $attribute) ===
                $this->castAttribute($key, $original);
        } elseif ($this->isClassCastable($key) && in_array($this->getCasts()[$key], [AsArrayObject::class, AsCollection::class])) {
            return $this->fromJson($attribute) === $this->fromJson($original);
        }

        return is_numeric($attribute) && is_numeric($original)
            && strcmp((string) $attribute, (string) $original) === 0;
    }

    /**
     * Transform a raw model value using mutators, casts, etc.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transformModelValue($key, $value)
    {
        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        } elseif ($this->hasAttributeGetMutator($key)) {
            return $this->mutateAttributeMarkedAttribute($key, $value);
        }

        // If the attribute exists within the cast array, we will convert it to
        // an appropriate native PHP type dependent upon the associated value
        // given with the key in the pair. Dayle made this comment line up.
        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }

        // If the attribute is listed as a date, we will convert it to a DateTime
        // instance on retrieval, which makes it quite convenient to work with
        // date fields without having to create a mutator for each property.
        if ($value !== null
            && \in_array($key, $this->getDates(), false)) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    /**
     * Append attributes to query when building a query.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function append($attributes)
    {
        $this->appends = array_unique(
            array_merge($this->appends, is_string($attributes) ? func_get_args() : $attributes)
        );

        return $this;
    }

    /**
     * Set the accessors to append to model arrays.
     *
     * @param  array  $appends
     * @return $this
     */
    public function setAppends(array $appends)
    {
        $this->appends = $appends;

        return $this;
    }

    /**
     * Return whether the accessor attribute has been appended.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function hasAppended($attribute)
    {
        return in_array($attribute, $this->appends);
    }

    /**
     * Get the mutated attributes for a given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = static::class;

        if (! isset(static::$mutatorCache[$class])) {
            static::cacheMutatedAttributes($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
     *
     * @param  string  $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        static::$getAttributeMutatorCache[$class] =
            collect($attributeMutatorMethods = static::getAttributeMarkedMutatorMethods($class))
                    ->mapWithKeys(function ($match) {
                        return [lcfirst(static::$snakeAttributes ? Str::snake($match) : $match) => true];
                    })->all();

        static::$mutatorCache[$class] = collect(static::getMutatorMethods($class))
                ->merge($attributeMutatorMethods)
                ->map(function ($match) {
                    return lcfirst(static::$snakeAttributes ? Str::snake($match) : $match);
                })->all();
    }

    /**
     * Get all of the attribute mutator methods.
     *
     * @param  mixed  $class
     * @return array
     */
    protected static function getMutatorMethods($class)
    {
        preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

        return $matches[1];
    }

    /**
     * Get all of the "Attribute" return typed attribute mutator methods.
     *
     * @param  mixed  $class
     * @return array
     */
    protected static function getAttributeMarkedMutatorMethods($class)
    {
        $instance = is_object($class) ? $class : new $class;

        return collect((new ReflectionClass($instance))->getMethods())->filter(function ($method) use ($instance) {
            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType &&
                $returnType->getName() === Attribute::class) {
                $method->setAccessible(true);

                if (is_callable($method->invoke($instance)->get)) {
                    return true;
                }
            }

            return false;
        })->map->name->values()->all();
    }
}
