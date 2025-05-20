<?php

namespace Illuminate\Validation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\DateRangeDoesNotOverlap;

class DateRangeValidationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register the validation extension
        $this->app->afterResolving('validator', function ($validator, $app) {
            $this->registerDateRangeValidator($validator);
        });
    }

    /**
     * Register the date range validation rule.
     *
     * @param  \Illuminate\Validation\Factory  $validator
     * @return void
     */
    protected function registerDateRangeValidator($validator)
    {
        $validator->extend('date_range_does_not_overlap', function ($attribute, $value, $parameters, $validator) {
            $table = $parameters[0] ?? null;
            $startColumn = $parameters[1] ?? 'start_date';
            $endColumn = $parameters[2] ?? 'end_date';
            $excludeId = $parameters[3] ?? null;
            $idColumn = $parameters[4] ?? 'id';

            if (! $table) {
                return false;
            }

            $rule = new DateRangeDoesNotOverlap($table, $startColumn, $endColumn, $excludeId, $idColumn);

            $fails = false;
            $rule->validate($attribute, $value, function () use (&$fails) {
                $fails = true;
            });

            return ! $fails;
        });
    }
}
