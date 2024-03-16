<?php

namespace Illuminate\Container;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class GiveConfig
{
    public function __construct(
        protected string $configKey,
        protected mixed $default = null,
    ) {
    }

    public function resolve($container): mixed
    {
        return $container->get('config')->get($this->configKey, $this->default);
    }
}
