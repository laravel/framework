<?php

namespace Illuminate\Tests\App\Models\JsonApi;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Factories\JsonApi\PostFactory;
use Illuminate\Tests\App\Http\Resources\JsonApi\ApiPostResource;

#[UseFactory(PostFactory::class)]
#[UseResource(ApiPostResource::class)]
class ApiPost extends Model
{
    use HasFactory;

    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany(ApiComment::class, 'post_id');
    }

    public function author()
    {
        return $this->belongsTo(ApiUser::class, 'user_id');
    }
}
