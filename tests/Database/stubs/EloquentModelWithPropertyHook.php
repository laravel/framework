<?php

use Illuminate\Database\Eloquent\Model;

return new class extends Model
{
    public $attributes = [
        'first_name' => null,
        'last_name' => null,
    ];

    public string $full_name {
        get => "$this->first_name $this->last_name";
        set (string $value) {
            [$firstName, $lastName] = explode(' ', $value);
            $this->attributes['first_name'] = $firstName;
            $this->attributes['last_name'] = $lastName;
        }
    }
};
