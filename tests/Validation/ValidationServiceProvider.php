<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\DistinctWithin;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Extend Validator with 'distinctWithin' rule
        Validator::extend('distinctWithin', function ($attribute, $value, $parameters, $validator) {
            $seconds = $parameters[0] ?? 60;

            return (new DistinctWithin($seconds))->passes($attribute, $value);
        });

        // Optional: customize error message replacer
        Validator::replacer('distinctWithin', function ($message, $attribute, $rule, $parameters) {
            $seconds = $parameters[0] ?? 60;

            return str_replace(':seconds', $seconds, $message);
        });
    }
}
