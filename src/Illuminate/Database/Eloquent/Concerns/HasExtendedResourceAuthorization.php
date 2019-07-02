<?php

    namespace Illuminate\Database\Eloquent\Concerns;


    trait HasExtendedResourceAuthorization
    {
        /**
         * The extended resource abilities which should be authorized when using authorizeResource
         *
         * @var array
         */
        public static $extendedResourceAbilities = [];

        /**
         * The extended resource methods without models which should be authorized when using authorizeResource
         *
         * @var array
         */
        public static $extendedResourceMethodsWithoutModel = [];

    }
