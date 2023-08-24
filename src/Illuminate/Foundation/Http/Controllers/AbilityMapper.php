<?php

namespace Illuminate\Foundation\Http\Controllers;

use Illuminate\Foundation\Http\Attributes\Ability;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionMethod;

class AbilityMapper
{
    /**
     * Indicates whether reflection is used to discover ability attributes.
     *
     * @var bool
     */
    protected static bool $discoverAbilityAttributes = false;

    /**
     * Map abilities to authorize resources with.
     *
     * @param  class-string  $class
     * @return array<string, string>
     */
    public function mapAbilitiesForClass(string $class): array
    {
        $abilities = static::defaultAbilityMap();

        if (static::$discoverAbilityAttributes) {
            $abilities = array_merge($abilities, static::mapAttributeAbilities($class));
        }

        return array_filter($abilities);
    }

    /**
     * Discover ability attributes.
     *
     * @param  bool  $value
     * @return void
     */
    public static function discoverAbilityAttributes(bool $value = true): void
    {
        static::$discoverAbilityAttributes = $value;
    }

    /**
     * Determine if ability attributes are discovered.
     *
     * @return bool
     */
    public static function discoversAbilityAttributes(): bool
    {
        return static::$discoverAbilityAttributes;
    }

    /**
     * Map abilities from attributes.
     *
     * @param  class-string  $class
     * @return array<string, string|null>
     * @throws \ReflectionException
     */
    protected function mapAttributeAbilities(string $class): array
    {
        $class = new ReflectionClass($class);

        return collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->mapWithKeys(function (ReflectionMethod $method) {
                if ($method->getName() === '__construct') {
                    return [];
                }

                $attributes = $method->getAttributes(Ability::class);

                $attribute = Arr::first($attributes);

                if ($attribute === null) {
                    return [];
                }

                return [$method->getName() => $attribute->newInstance()?->ability];
            })
            ->toArray();
    }

    /**
     * Default ability map.
     *
     * @return array<string, string>
     */
    protected function defaultAbilityMap(): array
    {
        return [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }
}
