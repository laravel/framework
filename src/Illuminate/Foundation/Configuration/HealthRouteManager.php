<?php

namespace Illuminate\Foundation\Configuration;

use Closure;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class HealthRouteManager
{
    /** @var Closure(?string): void|null */
    protected static ?Closure $responseCallback = null;

    public static function register(string $route): void
    {
        Route::get($route, function () {
            $exception = null;

            try {
                Event::dispatch(new DiagnosingHealth);
            } catch (\Throwable $e) {
                if (app()->hasDebugModeEnabled()) {
                    throw $e;
                }

                report($e);

                $exception = $e->getMessage();
            }

            if (static::$responseCallback) {
                return call_user_func(static::$responseCallback, $exception);
            }

            return response(View::file(__DIR__.'/../resources/health-up.blade.php', [
                'exception' => $exception,
            ]), status: $exception ? 500 : 200);
        });
    }

    /**
     * @param  Closure(?string $exception): mixed  $callback
     */
    public static function respondUsing(Closure $callback): void
    {
        static::$responseCallback = $callback;
    }
}
