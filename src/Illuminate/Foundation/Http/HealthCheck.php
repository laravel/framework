<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;

class HealthCheck
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
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

        $status = $exception ? 500 : 200;

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $exception ? 'down' : 'up',
            ], $status);
        }

        return response(View::file(__DIR__.'/../resources/health-up.blade.php', [
            'exception' => $exception,
        ]), status: $status);
    }
}
