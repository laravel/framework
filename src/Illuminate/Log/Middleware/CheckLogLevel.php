<?php

namespace Illuminate\Log\Middleware;

use Illuminate\Contracts\Log\Channel;

class CheckLogLevel
{
    public function handle(Channel $channel, $next, $level = 'debug')
    {
        foreach ($channel->getLogger()->getHandlers() as $handler) {
            $handler->setLevel($level);
        }

        return $next($channel);
    }
}
