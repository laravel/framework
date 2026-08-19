<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class NoConstraintsTestUser extends Model
{
    public static $selectedToken;

    public $timestamps = false;
    protected $table = 'users';
    protected $guarded = [];

    public function selectedUserTokens()
    {
        return $this->hasMany(NoConstraintsTestToken::class, 'tokenable_id')
            ->where('tokenable_id', static::$selectedToken->tokenable->getKey());
    }
}
