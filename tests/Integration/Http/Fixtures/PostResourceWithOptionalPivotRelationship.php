<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithOptionalPivotRelationship extends PostResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subscription' => $this->whenPivotLoaded(Subscription::class, static function () {
                return [
                    'foo' => 'bar',
                ];
            }),
            'custom_subscription' => $this->whenPivotLoadedAs('accessor', Subscription::class, static function () {
                return [
                    'foo' => 'bar',
                ];
            }),
        ];
    }
}
