<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Validation\Validator make(array $data, array $rules, array $messages, array $customAttributes) Create a new Validator instance.
 * @method static void validate(array $data, array $rules, array $messages, array $customAttributes) Validate the given data against the provided rules.
 * @method static void extend(string $rule, \Closure | string $extension, string $message) Register a custom validator extension.
 * @method static void extendImplicit(string $rule, \Closure | string $extension, string $message) Register a custom implicit validator extension.
 * @method static void extendDependent(string $rule, \Closure | string $extension, string $message) Register a custom dependent validator extension.
 * @method static void replacer(string $rule, \Closure | string $replacer) Register a custom validator message replacer.
 * @method static void resolver(\Closure $resolver) Set the Validator instance resolver.
 * @method static \Illuminate\Contracts\Translation\Translator getTranslator() Get the Translator implementation.
 * @method static \Illuminate\Validation\PresenceVerifierInterface getPresenceVerifier() Get the Presence Verifier implementation.
 * @method static void setPresenceVerifier(\Illuminate\Validation\PresenceVerifierInterface $presenceVerifier) Set the Presence Verifier implementation.
 *
 * @see \Illuminate\Validation\Factory
 */
class Validator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'validator';
    }
}
