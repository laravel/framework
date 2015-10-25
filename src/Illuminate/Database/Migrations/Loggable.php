<?php

namespace Illuminate\Database\Migrations;

/**
 * Introduces logging features into a class.
 */
trait Loggable
{
    /**
     * @var bool A boolean that indicates whether logging should happen; default is true.
     */
    protected $logging = true;

    /**
     * Determines if a migrator should log migrations.
     *
     * @return bool
     */
    protected function shouldLog()
    {
        return $this->logging == true;
    }

    /**
     * Disables logging.
     */
    protected function disableLogging()
    {
        $this->logging = false;
    }
}
