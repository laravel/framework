<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class VisibleCommentResource extends JsonApiResource
{
    public string $type = 'visible_comments';

    /**
     * The resource's attributes.
     */
    public $attributes = [
        'content',
    ];

    public function toType(Request $request)
    {
        return $this->type;
    }

    public function withType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
