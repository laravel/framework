<?php

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutCsrf
{
    /**
     * @before
     */
    public function disableCsrfForAllTests()
    {
        if (method_exists($this, 'withoutCsrf')) {
            $this->withoutCsrf();
        } else {
            throw new Exception('Unable to disable CSRF middleware. CrawlerTrait not used.');
        }
    }
}
