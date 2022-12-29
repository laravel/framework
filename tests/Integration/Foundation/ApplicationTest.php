<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\View\ViewException;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class ApplicationTest extends TestCase
{
    public function testItCanThrowOnViewResolution()
    {
        View::addLocation(__DIR__.'/views');
        Schema::create('foo', function ($table) {
            $table->id();
        });
        Schema::create('bar', function ($table) {
            $table->id();
        });
        Route::get('test-route', function () {
            DB::table('foo')->get();

            return view('bar');
        });
        DB::enableQueryLog();
        $this->withoutExceptionHandling();

        $this->get('test-route')->assertOk();
        $this->assertCount(2, DB::getQueryLog());

        $this->app->preventQueriesWhilePreparingResponse();
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Queries are being prevented. Attempting to run query [select count(*) as aggregate from "bar"].');

        $this->get('test-route');
    }

    public function testItCanThrowOnJsonResourceResolution()
    {
        Schema::create('foo', function ($table) {
            $table->id();
        });
        Schema::create('bar', function ($table) {
            $table->id();
        });
        Route::get('test-route', function () {
            DB::table('foo')->get();

            return new class('xxxx') extends JsonResource
            {
                public function toArray($request)
                {
                    return [
                        'bar_count' => $this->when(true, DB::table('bar')->count()),
                    ];
                }
            };
        });
        DB::enableQueryLog();
        $this->withoutExceptionHandling();

        $this->get('test-route')->assertOk();
        $this->assertCount(2, DB::getQueryLog());

        $this->app->preventQueriesWhilePreparingResponse();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queries are being prevented. Attempting to run query [select count(*) as aggregate from "bar"].');

        $this->get('test-route');
    }

    public function testItCanRunQueriesAfterResponseResolved()
    {
        Schema::create('foo', function ($table) {
            $table->id();
        });
        Route::get('test-route', function () {
            return new class('xxxx') extends JsonResource
            {
                public function toArray($request)
                {
                    return [
                        //
                    ];
                }
            };
        })->middleware(TestMiddleware::class);
        DB::enableQueryLog();
        $this->withoutExceptionHandling();

        $this->app->preventQueriesWhilePreparingResponse();
        $this->get('test-route')->assertOk();

        $this->assertCount(1, DB::getQueryLog());
    }

    public function testItcanRestoreValue()
    {
        Schema::create('foo', function ($table) {
            $table->id();
        });
        Route::get('test-route', function () {
            return new class('xxxx') extends JsonResource
            {
                public function toArray($request)
                {
                    return [
                        //
                    ];
                }
            };
        })->middleware(TestMiddleware::class);
        DB::enableQueryLog();
        $this->withoutExceptionHandling();

        $this->app->preventQueriesWhilePreparingResponse();
        $this->app->allowQueriesWhilePreparingResponse();
        $this->get('test-route')->assertOk();

        $this->assertCount(1, DB::getQueryLog());
    }
}

class TestMiddleware
{
    public function handle($request, $next)
    {
        tap($next($request), function () {
            DB::table('foo')->count();
        });
    }
}
