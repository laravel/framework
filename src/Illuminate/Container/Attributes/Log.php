<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Log implements ContextualAttribute
{
    /**
     * Create a new class instance.
     * @param  string|null  $channel  The log configuration's channel name.
     * @param  string|null  $name  The name to prefix all logs with. Only to be used with Monolog drivers.
     */
    public function __construct(
        public ?string $channel = null,
        public ?string $name = null,
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
        $logger = $container->make('log')->channel($attribute->channel);
        if ($attribute->name !== null) {
            $logger = $logger->withName($attribute->name);
        }

        return $logger;
    }
}
