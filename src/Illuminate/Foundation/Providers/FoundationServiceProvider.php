<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\AggregateServiceProvider;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
     *
     * @var array
     */
    protected $providers = [
        FormRequestServiceProvider::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerRequestValidation();
        $this->registerRequestSignatureValidation();
    }

    /**
     * Register the "validate" macro on the request.
     *
     * @return void
     */
    public function registerRequestValidation()
    {
        Request::macro('validate', function (array $rules, ...$params) {
            return validator()->validate($this->all(), $rules, ...$params);
        });
    }

    /**
     * Register the "hasValidSignature" macro on the request.
     *
     * @return void
     */
    public function registerRequestSignatureValidation()
    {
        Request::macro('hasValidSignature', function ($absolute = true) {
            return URL::hasValidSignature($this, $absolute);
        });
    }
}
