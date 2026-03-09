<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class JsonApiError implements Arrayable, JsonSerializable
{
    /**
     * A unique identifier for this particular occurrence of the problem.
     */
    protected ?string $id = null;

    /**
     * The HTTP status code applicable to this problem, expressed as a string value.
     */
    protected ?string $status = null;

    /**
     * An application-specific error code, expressed as a string value.
     */
    protected ?string $code = null;

    /**
     * A short, human-readable summary of the problem.
     */
    protected ?string $title = null;

    /**
     * A human-readable explanation specific to this occurrence of the problem.
     */
    protected ?string $detail = null;

    /**
     * An object containing references to the primary source of the error.
     *
     * @var array{pointer?: string, parameter?: string, header?: string}
     */
    protected array $source = [];

    /**
     * A links object containing an "about" and/or "type" link.
     *
     * @var array{about?: string, type?: string}
     */
    protected array $links = [];

    /**
     * A meta object containing non-standard meta-information about the error.
     */
    protected array $meta = [];

    /**
     * Create a new JSON:API error instance.
     */
    public function __construct(?string $detail = null, ?string $status = null)
    {
        $this->detail = $detail;
        $this->status = $status;
    }

    /**
     * Create a new JSON:API error instance.
     */
    public static function make(?string $detail = null, ?string $status = null): static
    {
        return new static($detail, $status);
    }

    /**
     * Set the unique identifier for this error occurrence.
     *
     * @return $this
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the HTTP status code for this error.
     *
     * @return $this
     */
    public function status(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the application-specific error code.
     *
     * @return $this
     */
    public function code(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set the short, human-readable summary.
     *
     * @return $this
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the human-readable explanation specific to this occurrence.
     *
     * @return $this
     */
    public function detail(string $detail): static
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Set a JSON Pointer to the value in the request document that caused the error.
     *
     * @return $this
     */
    public function pointer(string $pointer): static
    {
        $this->source['pointer'] = $pointer;

        return $this;
    }

    /**
     * Set the name of the query parameter that caused the error.
     *
     * @return $this
     */
    public function parameter(string $parameter): static
    {
        $this->source['parameter'] = $parameter;

        return $this;
    }

    /**
     * Set the name of the request header that caused the error.
     *
     * @return $this
     */
    public function header(string $header): static
    {
        $this->source['header'] = $header;

        return $this;
    }

    /**
     * Set the "about" link.
     *
     * @return $this
     */
    public function about(string $url): static
    {
        $this->links['about'] = $url;

        return $this;
    }

    /**
     * Set the "type" link.
     *
     * @return $this
     */
    public function type(string $url): static
    {
        $this->links['type'] = $url;

        return $this;
    }

    /**
     * Set the meta object.
     *
     * @return $this
     */
    public function meta(array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'status' => $this->status,
            'code' => $this->code,
            'title' => $this->title,
            'detail' => $this->detail,
            'source' => $this->source ?: null,
            'links' => $this->links ?: null,
            'meta' => $this->meta ?: null,
        ], fn ($value) => ! is_null($value));
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
