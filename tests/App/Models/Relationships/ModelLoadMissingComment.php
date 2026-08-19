<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ModelLoadMissingComment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function mentionsUsers()
    {
        return $this->belongsToMany(ModelLoadMissingUser::class, 'comment_mentions_users', 'comment_id', 'user_id');
    }

    public function content(): Attribute
    {
        return new Attribute(function (?string $value) {
            return preg_replace_callback('/<u:(\d+)>/', function ($matches) {
                return '@'.$this->mentionsUsers->find($matches[1])?->name;
            }, $value);
        });
    }
}
