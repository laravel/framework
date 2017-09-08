<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Mappings for custom rules for the application.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Adds application rules to validator.
     *
     * @return void
     */
    public function registerRules()
    {
        foreach ($this->rules as $rule => $class) {
            $instance = $this->app->make($class);

            Validator::extend($rule, [$instance, 'passes'], $instance->message());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->registerRules();
    }
}
