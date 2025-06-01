<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Provide implements ContextualAttribute
{
    /**
     * Provide a concrete class implementation for dependency injection.
     * 
     * Note: This attribute requires a concrete class name. Abstract classes 
     * and interfaces are not supported and will result in binding resolution errors.
     *
     * @template T
     * @param class-string<T> $class The concrete class to instantiate
     * @param  array|null  $params  Constructor parameters (optional)
     * 
     * @example Basic usage:
     * #[Provide(EloquentUserRepository::class)]
     * private UserRepository $EloquentUserRepository
     * 
     * @example With constructor parameters:
     * #[Provide(SendGridEmailService::class, ['template' => 'welcome', 'from' => 'noreply@app.com'])]
     * private EmailService $emailService
     */
    public function __construct(
        public string $class,
        public array $params = []
    ) {}

    /**
     * Resolve the dependency.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container): mixed
    {
        return $container->make($attribute->class, $attribute->params);
    }
}
