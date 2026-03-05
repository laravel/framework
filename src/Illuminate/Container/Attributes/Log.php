<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use UnitEnum;
use function Illuminate\Support\enum_value;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Log implements ContextualAttribute
{
    /**
     * Create a new class instance.
     * @param  string|UnitEnum|null  $channel  The log configuration's channel name.
     * @param  string|UnitEnum|null  $name  The name to prefix all logs with. Only to be used with Monolog drivers.
     */
    public function __construct(
        public string|UnitEnum|null $channel = null,
        public string|UnitEnum|null $name = null,
    ) {
    }

    /**
     * Resolve the log channel.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return \Psr\Log\LoggerInterface
     */
    public static function resolve(self $attribute, Container $container)
    {
        $logger = $container->make('log')->channel(enum_value($attribute->channel));
        if ($attribute->name !== null) {
            $logger = $logger->withName(enum_value($attribute->name));
        }

        return $logger;
    }
}
