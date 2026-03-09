<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class JsonApiErrorResponse extends JsonResponse
{
    /**
     * Create a new JSON:API error response.
     *
     * @param  array<int, \Illuminate\Http\Resources\JsonApi\JsonApiError|array>  $errors
     */
    public function __construct(array $errors = [], int $status = 400, array $headers = [])
    {
        $data = [
            'errors' => array_map(
                fn ($error) => $error instanceof JsonApiError ? $error->toArray() : $error,
                array_values($errors)
            ),
            ...JsonApiResource::jsonApiBlock(),
        ];

        parent::__construct($data, $status, $headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Create a new JSON:API error response.
     *
     * @param  array<int, \Illuminate\Http\Resources\JsonApi\JsonApiError|array>  $errors
     */
    public static function make(array $errors = [], int $status = 400, array $headers = []): static
    {
        return new static($errors, $status, $headers);
    }

    /**
     * Create a JSON:API error response from a validation exception.
     */
    public static function fromValidationException(ValidationException $exception): static
    {
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = JsonApiError::make($message, (string) $exception->status)
                    ->title('Validation Error')
                    ->pointer('/data/attributes/'.$field);
            }
        }

        return static::make($errors, $exception->status);
    }

    /**
     * Create a JSON:API error response from a throwable.
     */
    public static function fromThrowable(Throwable $exception, bool $debug = false): static
    {
        $isHttpException = $exception instanceof HttpExceptionInterface;

        $status = $isHttpException ? $exception->getStatusCode() : 500;
        $headers = $isHttpException ? $exception->getHeaders() : [];

        $error = JsonApiError::make(status: (string) $status);

        $message = $isHttpException ? $exception->getMessage() : null;

        $error->title($message ?: 'Server Error');

        if ($debug) {
            $error->detail($exception->getMessage())
                ->meta([
                    'exception' => $exception::class,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]);
        }

        return static::make([$error], $status, $headers);
    }
}
