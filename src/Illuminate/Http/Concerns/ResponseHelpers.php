<?php

namespace Illuminate\Http\Concerns;

use Illuminate\Http\Response;

trait ResponseHelpers
{
    public static function ok(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_OK, $headers);
    }

    public static function created(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_CREATED, $headers);
    }

    public static function accepted(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_ACCEPTED, $headers);
    }

    public static function noContent(array $headers = []): static
    {
        return new static(status: Response::HTTP_NO_CONTENT, headers: $headers);
    }

    public static function movePermanently(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_MOVED_PERMANENTLY, $headers);
    }

    public static function found(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_FOUND, $headers);
    }

    public static function notModified(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_NOT_MODIFIED, $headers);
    }

    public static function temporaryRedirect(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_TEMPORARY_REDIRECT, $headers);
    }

    public static function permanentRedirect(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_PERMANENTLY_REDIRECT, $headers);
    }

    public static function badRequest(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_BAD_REQUEST, $headers);
    }

    public static function unauthorized(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_UNAUTHORIZED, $headers);
    }

    public static function paymentRequired(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_PAYMENT_REQUIRED, $headers);
    }

    public static function forbidden(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_FORBIDDEN, $headers);
    }

    public static function notFound(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_NOT_FOUND, $headers);
    }

    public static function methodNotAllowed(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_METHOD_NOT_ALLOWED, $headers);
    }

    public static function notAcceptable(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_NOT_ACCEPTABLE, $headers);
    }

    public static function requestTimeout(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_REQUEST_TIMEOUT, $headers);
    }

    public static function conflict(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_CONFLICT, $headers);
    }

    public static function gone(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_GONE, $headers);
    }

    public static function unsupportedMediaType(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_UNSUPPORTED_MEDIA_TYPE, $headers);
    }

    public static function unprocessableEntity(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_UNPROCESSABLE_ENTITY, $headers);
    }

    public static function tooManyRequests(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_TOO_MANY_REQUESTS, $headers);
    }

    public static function internalServerError(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_INTERNAL_SERVER_ERROR, $headers);
    }

    public static function serviceUnavailable(mixed $data = [], array $headers = []): static
    {
        return new static($data, Response::HTTP_SERVICE_UNAVAILABLE, $headers);
    }
}
