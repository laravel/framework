<?php

namespace Illuminate\Tests\Support;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class DateRouteBindingTest extends TestCase
{
    public function testBindsDate()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1436040000));

        Route::get('/foo/{date}/bar', function (Carbon $date) {
            return $date->toDateTimeString();
        })->middleware(SubstituteBindings::class);

        $this->assertSame('2015-07-04 00:00:00', $this->get('foo/2015-07-04/bar')->original);
        $this->get('foo/invalid/bar')->assertNotFound();
    }

    public function testBindsFormattedDatetime()
    {
        $date = Carbon::createFromTimestamp(1436040000)->format('YmdHis');

        Route::get('/foo/{date:YmdHis}/bar', function (Carbon $date) {
            return $date->getTimestamp();
        })->middleware(SubstituteBindings::class);

        $this->assertSame(1436040000, $this->get("foo/$date/bar")->original);
        $this->get('foo/invalid/bar')->assertNotFound();
    }
}
