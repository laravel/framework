<?php

namespace Illuminate\Routing\Concerns;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionMethod;

/**
 * Trait HasCustomAttributes.
 *
 * This trait allows Laravel controllers to handle custom PHP attributes on controller methods.
 * Attributes are metadata annotations that can be added to classes, methods, or properties.
 * They provide additional context or functionality and can be retrieved at runtime using reflection.
 *
 * This trait specifically retrieves attributes from controller methods and invokes their `handle` method (if it exists)
 * before executing the controller action. Attributes must implement the `ICustomAttribute` interface to be processed.
 *
 * For more information on PHP attributes, see the official documentation:
 *
 * @link https://www.php.net/manual/en/language.attributes.overview.php
 *
 * Example usage:
 * ```php
 * #[SomeCustomAttribute]
 * public function myControllerMethod()
 * {
 *     // Action logic here
 * }
 * ```
 */
trait HasCustomAttributes
{
    /**
     * Call a controller action with custom attribute handling.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function callAction($method, $parameters)
    {
        $reflection = new ReflectionMethod($this, $method);

        $attributes = $this->getCustomAttributes($reflection);

        $attributes->each(function (ReflectionAttribute $attribute) {
            $instance = $attribute->newInstance();

            if (method_exists($instance, 'handle')) {
                $attribute->newInstance()->handle(...$attribute->getArguments());
            }
        });

        return parent::callAction($method, $parameters);
    }

    private function getCustomAttributes(ReflectionMethod $reflection): Collection
    {
        return collect($reflection->getAttributes())
            ->filter(fn (ReflectionAttribute $attr) => $attr->newInstance() instanceof ICustomAttribute);
    }
}
