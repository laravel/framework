<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Constraints\CountInDatabase;
use Illuminate\Testing\Constraints\HasInDatabase;
use Illuminate\Testing\Constraints\SoftDeletedInDatabase;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;

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
     * Assert that the given route URL is the current request URL..
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
