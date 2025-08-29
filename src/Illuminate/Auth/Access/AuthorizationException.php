<?php

namespace Illuminate\Auth\Access;

use Exception;
use Throwable;

class AuthorizationException extends Exception
{
    /**
     * The response from the gate.
     *
     * @var \Illuminate\Auth\Access\Response|null
     */
    protected ?Response $response = null;

    /**
     * The HTTP response status code.
     *
     * @var int|null
     */
    protected ?int $status = null;

    /**
     * Create a new authorization exception instance.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @param  \Throwable|null  $previous
     */
    public function __construct(?string $message = null, mixed $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?? 'This action is unauthorized.', 0, $previous);

        $this->code = $code ?: 0;
    }

    /**
     * Create a new authorization exception with a 403 status.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return static
     */
    public static function forbidden(?string $message = null, mixed $code = null): static
    {
        return (new static($message, $code))->asForbidden();
    }

    /**
     * Create a new authorization exception with a 404 status.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return static
     */
    public static function notFound(?string $message = null, mixed $code = null): static
    {
        return (new static($message, $code))->asNotFound();
    }

    /**
     * Create a new authorization exception with a 401 status.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return static
     */
    public static function unauthorized(?string $message = null, mixed $code = null): static
    {
        return (new static($message, $code))->asUnauthorized();
    }

    /**
     * Get the response from the gate.
     *
     * @return \Illuminate\Auth\Access\Response|null
     */
    public function response(): ?Response
    {
        return $this->response;
    }

    /**
     * Get the authorization message, preferring the response message if available.
     *
     * @return string
     */
    public function getAuthorizationMessage(): string
    {
        return $this->response?->message() ?? $this->getMessage();
    }

    /**
     * Set the response from the gate.
     *
     * @param  \Illuminate\Auth\Access\Response  $response
     * @return $this
     */
    public function setResponse(Response $response): static
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Set the HTTP response status code.
     *
     * @param  int|null  $status
     * @return $this
     */
    public function withStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the HTTP response status code to 404.
     *
     * @return $this
     */
    public function asNotFound(): static
    {
        return $this->withStatus(404);
    }

    /**
     * Set the HTTP response status code to 403.
     *
     * @return $this
     */
    public function asForbidden(): static
    {
        return $this->withStatus(403);
    }

    /**
     * Set the HTTP response status code to 401.
     *
     * @return $this
     */
    public function asUnauthorized(): static
    {
        return $this->withStatus(401);
    }

    /**
     * Determine if the HTTP status code has been set.
     *
     * @return bool
     */
    public function hasStatus(): bool
    {
        return $this->status !== null;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int|null
     */
    public function status(): ?int
    {
        return $this->status;
    }

    /**
     * Create a deny response object from this exception.
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function toResponse(): Response
    {
        return Response::deny($this->message, $this->code)->withStatus($this->status);
    }
}
