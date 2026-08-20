<?php

namespace Illuminate\Tests\App\Models\JsonApi;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Factories\JsonApi\CommentFactory;
use Illuminate\Tests\App\Http\Resources\JsonApi\CommentResource;

#[UseFactory(CommentFactory::class)]
#[UseResource(CommentResource::class)]
class ApiComment extends Model
{
    use HasFactory;

    protected $table = 'comments';

    public function post()
    {
        return $this->belongsTo(ApiPost::class);
    }

    public function commenter()
    {
        return $this->belongsTo(ApiUser::class, 'user_id');
    }
}
