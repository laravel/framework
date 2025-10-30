<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnonymousJsonApiResource extends JsonApiResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param  \Illuminate\Http\Resources\Json\JsonResource  $jsonResource
     */
    public function __construct(
        $resource,
        protected JsonResource $jsonResource,
        protected array $jsonApiLinks = [],
        protected array $jsonApiMeta = [],
    ) {
        parent::__construct($resource);
    }

    /**
     * Resource "links" for JSON:API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function links(Request $request)
    {
        return $this->jsonApiLinks;
    }

    /**
     * Resource "meta" for JSON:API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function meta(Request $request)
    {
        return $this->jsonApiMeta;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    #[\Override]
    public function toArray(Request $request)
    {
        return $this->jsonResource->toArray($request);
    }
}
