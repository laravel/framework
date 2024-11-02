<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Rfc4122\UuidV2;
use Ramsey\Uuid\Uuid as UuidUuid;
use Ramsey\Uuid\UuidFactory;
use ReflectionProperty;

class Uuid implements Rule, ValidatorAwareRule
{
    /**
     * The current validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * @var int<0, 8>|'max'
     */
    protected string|int $version;

    /**
     * Usually, the MAC address (can be random)
     * Supported by v1, v2, and v6.
     */
    protected string $node;

    /**
     * @var ':'|'-'
     */
    protected string $separator = ':';

    /**
     * Usually, the namespace
     * Supported by v3 and v5.
     */
    protected string $domain;

    /**
     * Usually, the namespace ID
     * Supported by v3 and v5.
     */
    protected string $identifier;

    /**
     * Supported by v1, v2, v6, and v7.
     *
     * @var callable
     */
    protected $dateTimeCallback;

    public function passes($attribute, $value)
    {
        if (! Str::isUuid($value)) {
            return false;
        }

        $factory = new UuidFactory;

        try {
            $factoryUuid = $factory->fromString($value);
        } catch (InvalidUuidStringException $ex) {
            return false;
        }

        $fields = $factoryUuid->getFields();

        if (! ($fields instanceof FieldsInterface)) {
            return false;
        }

        foreach (['version', 'node', 'domain', 'identifier', 'dateTimeCallback'] as $prop) {
            if (! $this->isInitialized($prop)) {
                continue;
            }

            if (
                $prop === 'version' && (
                    (in_array($this->version, [0, 'nil'], true) && ! $fields->isNil()) ||
                    ($this->version === 'max' && ! $fields->isMax()) ||
                    (! in_array($this->version, [0, 'nil', 'max']) && $fields->getVersion() !== $this->version)
                )
            ) {
                return false;
            }

            if ($prop === 'node' && in_array($fields->getVersion(), [
                UuidUuid::UUID_TYPE_TIME,
                UuidUuid::UUID_TYPE_DCE_SECURITY,
                UuidUuid::UUID_TYPE_REORDERED_TIME,
            ]) && $this->node !== $this->formatMacAddress($fields->getNode()->toString())) {
                return false;
            }

            if (
                $prop === 'domain' &&
                $fields->getVersion() === UuidUuid::UUID_TYPE_DCE_SECURITY &&
                $this->toV2($fields, $factory)->getLocalDomainName() !== $this->domain
            ) {
                return false;
            }

            if (
                $prop === 'identifier' &&
                $fields->getVersion() === UuidUuid::UUID_TYPE_DCE_SECURITY &&
                $this->toV2($fields, $factory)->getLocalIdentifier()->toString() !== $this->identifier
            ) {
                return false;
            }

            if (
                $prop === 'dateTimeCallback' &&
                $this->dateTimeCallback !== null &&
                in_array($fields->getVersion(), [
                    UuidUuid::UUID_TYPE_TIME,
                    UuidUuid::UUID_TYPE_DCE_SECURITY,
                    UuidUuid::UUID_TYPE_REORDERED_TIME,
                    UuidUuid::UUID_TYPE_UNIX_TIME,
                ]) &&
                ! call_user_func($this->dateTimeCallback, Carbon::createFromId($factoryUuid))
            ) {
                return false;
            }
        }

        return true;
    }

    private function toV2(FieldsInterface $fields, UuidFactory $factory)
    {
        $features = new FeatureSet();

        return new UuidV2($fields, $factory->getNumberConverter(), $factory->getCodec(), $features->getTimeConverter());
    }

    private function formatMacAddress(string $macAddress): string
    {
        return Str::upper(implode($this->separator, str_split($macAddress, 2)));
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.uuid');

        return $message === 'validation.uuid'
            ? ['The selected UUID (:attribute) is invalid.']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        return tap($this, fn () => $this->validator = $validator);
    }

    public function version(string|int $version)
    {
        return tap($this, fn () => $this->version = $version);
    }

    public function node(string $node)
    {
        return tap($this, fn () => $this->node = Str::upper($node));
    }

    public function domain(string|int $domain)
    {
        return tap($this, fn () => $this->domain = is_int($domain) ? UuidUuid::DCE_DOMAIN_NAMES[$domain] : $domain);
    }

    public function identifier(string|int $identifier)
    {
        return tap($this, fn () => $this->identifier = (string) $identifier);
    }

    public function dateTime(callable $callback)
    {
        return tap($this, fn () => $this->dateTimeCallback = $callback);
    }

    private function isInitialized(string $property)
    {
        return (new ReflectionProperty($this, $property))->isInitialized($this);
    }
}
