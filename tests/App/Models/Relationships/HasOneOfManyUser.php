<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneOfManyUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
    public $timestamps = false;

    public function latest_login()
    {
        return $this->hasOne(Login::class, 'user_id')->ofMany();
    }

    public function states()
    {
        return $this->hasMany(State::class, 'user_id');
    }

    public function latest_updated_state()
    {
        return $this->hasOne(State::class, 'user_id')->ofMany('updated_at', 'max');
    }

    public function oldest_updated_state()
    {
        return $this->hasOne(State::class, 'user_id')->ofMany('updated_at', 'min');
    }

    public function latest_updated_latest_created_state()
    {
        return $this->hasOne(State::class, 'user_id')->ofMany([
            'updated_at' => 'max',
            'created_at' => 'max',
        ]);
    }

    public function oldest_updated_oldest_created_state()
    {
        return $this->hasOne(State::class, 'user_id')->ofMany([
            'updated_at' => 'min',
            'created_at' => 'min',
        ]);
    }
}
