<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Attributes\UseResourceCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Http\Resources\EloquentResourceTestJsonResourceCollection;

#[UseResourceCollection(EloquentResourceTestJsonResourceCollection::class)]
class EloquentResourceTestResourceModelWithUseResourceCollectionAttribute extends Model
{
    //
}
