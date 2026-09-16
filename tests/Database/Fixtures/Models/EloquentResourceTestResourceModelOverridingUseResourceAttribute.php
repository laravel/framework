<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Tests\Database\Fixtures\Resources\EloquentResourceCollectionTestResource;

#[UseResource(EloquentResourceCollectionTestResource::class)]
class EloquentResourceTestResourceModelOverridingUseResourceAttribute extends EloquentResourceTestResourceModelWithUseResourceAttribute
{
    //
}
