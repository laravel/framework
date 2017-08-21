<?php

namespace Illuminate\Contracts\Logging;

interface Configurator
{
    /**
     * Create and configure the logger.
     *
     * @return \Illuminate\Contracts\Logging\Log
     */
    public function configure();
}
