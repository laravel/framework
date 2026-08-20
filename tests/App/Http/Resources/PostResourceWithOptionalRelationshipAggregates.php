<?php

namespace Illuminate\Tests\App\Http\Resources;

class PostResourceWithOptionalRelationshipAggregates extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'average_rating' => $this->whenAggregated('comments', 'rating', 'avg'),
            'minimum_rating' => $this->whenAggregated('comments', 'rating', 'min'),
            'maximum_rating' => $this->whenAggregated('comments', 'rating', 'max', fn ($avg) => "$avg ratings", 'Default Value'),
        ];
    }
}
