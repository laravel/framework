<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PostStringPrimaryKey extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'post_string_key';

    protected $keyType = 'string';

    protected $fillable = ['title', 'id'];

    public function tags()
    {
        return $this->belongsToMany(TagStringPrimaryKey::class, 'post_tag_string_key', 'post_id', 'tag_id');
    }
}
