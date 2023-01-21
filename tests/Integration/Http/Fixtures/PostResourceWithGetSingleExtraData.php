<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithGetSingleExtraData extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'is_admin' => $this->getAddition('is_admin', false),
        ];
    }
}
