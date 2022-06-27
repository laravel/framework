<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void excludeUnvalidatedArrayKeys()
 * @method static void extend(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void extendDependent(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static void extendImplicit(string $rule, \Closure|string $extension, string|null $message = null)
 * @method static \Illuminate\Contracts\Container\Container|null getContainer()
 * @method static \Illuminate\Validation\PresenceVerifierInterface getPresenceVerifier()
 * @method static \Illuminate\Contracts\Translation\Translator getTranslator()
 * @method static void includeUnvalidatedArrayKeys()
 * @method static \Illuminate\Validation\Validator make(array $data, array $rules, array $messages = [], array $customAttributes = [])
 * @method static void replacer(string $rule, \Closure|string $replacer)
 * @method static void resolver(\Closure $resolver)
 * @method static \Illuminate\Validation\Factory setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static void setPresenceVerifier(\Illuminate\Validation\PresenceVerifierInterface $presenceVerifier)
 * @method static array validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
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
