<?php

namespace Illuminate\Routing\Concerns;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionMethod;

trait HasCustomAttributes
{
    public function callAction($method, $parameters)
    {
        $reflection = new ReflectionMethod($this, $method);

        $attributes = $this->gteCustomAttributes($reflection);

        $attributes->each(function (ReflectionAttribute $attribute) {
            $instance = $attribute->newInstance();

            if(method_exists($instance, 'handle')) {
                $attribute->newInstance()->handle(...$attribute->getArguments());
            }
        });

        return parent::callAction($method, $parameters);
    }

    private function gteCustomAttributes(ReflectionMethod $reflection): Collection
    {
        return collect($reflection->getAttributes())
            ->filter(fn(ReflectionAttribute $attr) => $attr->newInstance() instanceof ICustomAttribute);
    }
}
