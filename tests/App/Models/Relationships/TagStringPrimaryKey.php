<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class TagStringPrimaryKey extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'tag_string_key';

    protected $keyType = 'string';

    protected $fillable = ['title', 'id'];

    public function posts()
    {
        return $this->belongsToMany(PostStringPrimaryKey::class, 'post_tag_string_key', 'tag_id', 'post_id');
    }
}
