<?php

namespace Tests\Foundation\Console\TestModels;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
        'description',
        'age',
        'is_active',
        'settings',
        'website_url',
    ];

    protected $casts = [
        'age' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'array',
        'email_verified_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    // Example relationships for testing
    public function posts()
    {
        return $this->hasMany(TestPost::class);
    }

    public function profile()
    {
        return $this->hasOne(TestProfile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(TestRole::class);
    }
}
