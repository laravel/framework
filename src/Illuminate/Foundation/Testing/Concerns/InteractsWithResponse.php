<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Testing\Assert as PHPUnit;

trait InteractsWithResponse
{
    /**
     * Assert that the given URL is the current request URL.
     *
     * @param  string  $url
     * @return $this
     */
    public function assertUrlIs(string $url)
    {
        $expectedUrl = $this->app['url']->to($url);

        PHPUnit::assertEquals(
            $expectedUrl,
            $this->getCurrentUrl(),
            'Current URL ['.$this->getCurrentUrl().'] does not match expected URL ['.$expectedUrl.'].'
        );

        return $this;
    }

    /**
     * Assert that the given route URL is the current request URL.
     *
     * @param  string  $route
     * @return $this
     */
    public function assertRouteIs(string $route)
    {
        return $this->assertUrlIs($this->app['url']->route($route));
    }

    /**
     * Get the current request URL.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
         return $this->app['url']->current();
    }
}
