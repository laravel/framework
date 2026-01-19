<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResource;

#[UseResource(EloquentResourceTestJsonResource::class)]
class EloquentResourceTestResourceModelWithUseResourceAttribute extends Model
{
    //
}
