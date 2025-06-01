<?php

namespace Illuminate\Validation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationSchemaLoader;

class ValidateRequestBySchema
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $schemaPath
     * @param  bool  $validateAll
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $schemaPath, bool $validateAll = false)
    {
        $validator = app('validator');

        // Get all request data or just route parameters and query
        $data = $validateAll ? $request->all() : array_merge(
            $request->route()->parameters(),
            $request->query()
        );

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
