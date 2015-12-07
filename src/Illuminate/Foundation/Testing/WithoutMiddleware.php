<?php

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutMiddleware
{
    /**
     * @before
     */
    public function disableMiddlewareForAllTests()
    {
        $this->afterApplicationCreated(function () {
            if (method_exists($this, 'withoutMiddleware')) {
                $this->withoutMiddleware();
            } else {
                throw new Exception('Unable to disable middleware. CrawlerTrait not used.');
            }
        });
    }
}
