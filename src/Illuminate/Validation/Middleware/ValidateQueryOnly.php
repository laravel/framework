<?php

namespace Illuminate\Validation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationSchemaLoader;

class ValidateQueryOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $schemaPath
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $schemaPath)
    {
        $validator = app('validator');

        // Get only query parameters
        $data = $request->query();

        // Load and validate using the flat rules format
        $schema = ValidationSchemaLoader::loadSchema($schemaPath);

        // Create a new validator instance
        $validatorInstance = $validator->make($data, $schema);

        if ($validatorInstance->fails()) {
            throw new ValidationException($validatorInstance);
        }

        return $next($request);
    }
}
