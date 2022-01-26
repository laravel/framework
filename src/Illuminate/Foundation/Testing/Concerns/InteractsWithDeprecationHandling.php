<?php

namespace Illuminate\Foundation\Testing\Concerns;

use ErrorException;

trait InteractsWithDeprecationHandling
{
    /**
     * The original deprecation handler.
     *
     * @var callable|null
     */
    protected $originalDeprecationHandler;

    /**
     * Restore deprecation handling.
     *
     * @return $this
     */
    protected function withDeprecationHandling()
    {
        if ($this->originalDeprecationHandler) {
            $previousHandler = set_error_handler(tap($this->originalDeprecationHandler, function () {
                $this->originalDeprecationHandler = null;
            }));

            if (method_exists($this, 'beforeApplicationDestroyed')) {
                $this->beforeApplicationDestroyed(function () use ($previousHandler) {
                    if (null !== $previousHandler) {
                        restore_error_handler();
                        $this->originalDeprecationHandler = null;
                    }
                });
            }
        }

        return $this;
    }

    /**
     * Disable deprecation handling for the test.
     *
     * @return $this
     */
    protected function withoutDeprecationHandling()
    {
        if ($this->originalDeprecationHandler == null) {
            $this->originalDeprecationHandler = set_error_handler(function ($level, $message, $file = '', $line = 0) {
                if (error_reporting() & $level) {
                    throw new ErrorException($message, 0, $level, $file, $line);
                }
            });

            if (method_exists($this, 'beforeApplicationDestroyed')) {
                $this->beforeApplicationDestroyed(function () {
                    if (null !== $this->originalDeprecationHandler) {
                        restore_error_handler();
                        $this->originalDeprecationHandler = null;
                    }
                });
            }
        }

        return $this;
    }
}
