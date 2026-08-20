<?php

namespace Illuminate\Tests\App\Models\JsonApi;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Factories\JsonApi\ProfileFactory;
use Illuminate\Tests\App\Http\Resources\JsonApi\ProfileResource;

#[UseResource(ProfileResource::class)]
#[UseFactory(ProfileFactory::class)]
class Profile extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(ApiUser::class);
    }
}
