<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

class RegisterFacades
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        Facades\Facade::clearResolvedInstances();

        Facades\Facade::setFacadeApplication($app);

        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', [
                'App' => Facades\App::class,
                'Arr' => Arr::class,
                'Artisan' => Facades\Artisan::class,
                'Auth' => Facades\Auth::class,
                'Blade' => Facades\Blade::class,
                'Broadcast' => Facades\Broadcast::class,
                'Bus' => Facades\Bus::class,
                'Cache' => Facades\Cache::class,
                'Config' => Facades\Config::class,
                'Cookie' => Facades\Cookie::class,
                'Crypt' => Facades\Crypt::class,
                'Date' => Facades\Date::class,
                'DB' => Facades\DB::class,
                'Eloquent' => Model::class,
                'Event' => Facades\Event::class,
                'File' => Facades\File::class,
                'Gate' => Facades\Gate::class,
                'Hash' => Facades\Hash::class,
                'Http' => Facades\Http::class,
                'Js' => Js::class,
                'Lang' => Facades\Lang::class,
                'Log' => Facades\Log::class,
                'Mail' => Facades\Mail::class,
                'Notification' => Facades\Notification::class,
                'Password' => Facades\Password::class,
                'Queue' => Facades\Queue::class,
                'RateLimiter' => Facades\RateLimiter::class,
                'Redirect' => Facades\Redirect::class,
                'Redis' => Facades\Redis::class,
                'Request' => Facades\Request::class,
                'Response' => Facades\Response::class,
                'Route' => Facades\Route::class,
                'Schema' => Facades\Schema::class,
                'Session' => Facades\Session::class,
                'Storage' => Facades\Storage::class,
                'Str' => Str::class,
                'URL' => Facades\URL::class,
                'Validator' => Facades\Validator::class,
                'View' => Facades\View::class,
            ]),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
