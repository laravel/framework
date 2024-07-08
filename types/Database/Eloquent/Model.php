<?php

namespace Illuminate\Types\Model;

use Illuminate\Database\Eloquent\Model;
use User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('UserFactory', User::factory(function ($attributes, $model) {
        assertType('array<string, mixed>', $attributes);
        assertType('User|null', $model);

        return ['string' => 'string'];
    }));
    assertType('UserFactory', User::factory(42, function ($attributes, $model) {
        assertType('array<string, mixed>', $attributes);
        assertType('User|null', $model);

        return ['string' => 'string'];
    }));

    assertType('Illuminate\Database\Eloquent\Builder<User>', User::query());
    assertType('Illuminate\Database\Eloquent\Builder<User>', $user->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<User>', $user->withTrashed());
    assertType('Illuminate\Database\Eloquent\Builder<User>', $user->onlyTrashed());
    assertType('Illuminate\Database\Eloquent\Builder<User>', $user->withoutTrashed());
    assertType('Illuminate\Database\Eloquent\Builder<User>', $user->prunable());
    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<Illuminate\Notifications\DatabaseNotification, User>', $user->notifications());

    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $user->newCollection([new User()]));
    assertType('Illuminate\Database\Eloquent\Collection<string, Illuminate\Types\Model\Post>', $user->newCollection(['foo' => new Post()]));

    assertType('bool', $user->restore());
    assertType('User', $user->restoreOrCreate());
    assertType('User', $user->createOrRestore());
}

class Post extends Model
{
}
