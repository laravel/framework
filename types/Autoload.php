<?php

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\HasDatabaseNotifications;

class User extends Authenticatable
{
    use HasDatabaseNotifications;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use MassPrunable;
    use SoftDeletes;

    protected static string $factory = UserFactory::class;
}

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [];
    }
}

class Post extends Model
{
}

enum UserType
{
}
