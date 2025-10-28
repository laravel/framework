<?php

namespace Illuminate\Http\Resources\Json;

class AnonymousJsonApiResource extends JsonApiResource
{
    /**
     * The anonymous resource's "version" for JSON:API.
     *
     * @var string|null
     */
    protected $version = null;

    #[\Override]
    public function version(Request $request)
    {
        return $this->version ?? static::$jsonApiVersion;
    }

    /**
     * Set the JSON:API version for the request.
     *
     * @param  string  $version
     * @return $this
     */
    public function withVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }
}
