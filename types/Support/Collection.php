<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

class User extends Authenticatable
{
}

$collection = Collection::make([new User]);
/** @var Arrayable<int, User> $arrayable */
$arrayable = [];
/** @var iterable<int, int> $iterable */
$iterable = [];
/** @var Traversable<int, string>  $traversable */
$traversable = [];

assertType('Illuminate\Support\Collection<int, User>', $collection);

foreach ($collection as $int => $user) {
    assertType('int', $int);
    assertType('User', $user);
}

assertType('Illuminate\Support\Collection<int, string>', collect(['string']));
assertType('Illuminate\Support\Collection<string, User>', collect(['string' => new User]));
assertType('Illuminate\Support\Collection<int, User>', collect($arrayable));
assertType('Illuminate\Support\Collection<int, User>', collect($collection));
assertType('Illuminate\Support\Collection<int, User>', collect($collection));
assertType('Illuminate\Support\Collection<int, int>', collect($iterable));
assertType('Illuminate\Support\Collection<int, string>', collect($traversable));

assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string']));
assertType('Illuminate\Support\Collection<string, User>', $collection::make(['string' => new User]));
assertType('Illuminate\Support\Collection<int, User>', $collection::make($arrayable));
assertType('Illuminate\Support\Collection<int, User>', $collection::make($collection));
assertType('Illuminate\Support\Collection<int, User>', $collection::make($collection));
assertType('Illuminate\Support\Collection<int, int>', $collection::make($iterable));
assertType('Illuminate\Support\Collection<int, string>', $collection::make($traversable));

assertType('Illuminate\Support\Collection<int, User>', $collection::times(10, function ($int) {
    // assertType('int', $int);

    return new User;
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->each(function ($user) {
    assertType('User', $user);
}));

assertType('Illuminate\Support\Collection<int, int>', $collection->range(1, 100));

assertType('Illuminate\Support\Collection<int, string>', $collection->wrap(['string']));
assertType('Illuminate\Support\Collection<string, User>', $collection->wrap(['string' => new User]));

assertType('array<int, string>', $collection->unwrap(['string']));
assertType('array<int, User>', $collection->unwrap(
    $collection
));

assertType('Illuminate\Support\Collection<int, User>', $collection::empty());

assertType('float|int|null', $collection->average());
assertType('float|int|null', $collection->average('string'));
assertType('float|int|null', $collection->average(function ($user) {
    assertType('User', $user);

    return 1;
}));
assertType('float|int|null', $collection->average(function ($user) {
    assertType('User', $user);

    return 0.1;
}));

assertType('float|int|null', $collection->median());
assertType('float|int|null', $collection->median('string'));
assertType('float|int|null', $collection->median(['string']));

assertType('array<int, float|int>|null', $collection->mode());
assertType('array<int, float|int>|null', $collection->mode('string'));
assertType('array<int, float|int>|null', $collection->mode(['string']));

assertType('Illuminate\Support\Collection<int, mixed>', $collection->collapse());

assertType('bool', $collection->some(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->some('string', '=', 'string'));

assertType('bool', $collection->containsStrict(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->containsStrict('string', 'string'));

assertType('float|int|null', $collection->avg());
assertType('float|int|null', $collection->avg('string'));
assertType('float|int|null', $collection->avg(function ($user) {
    assertType('User', $user);

    return 1;
}));
assertType('float|int|null', $collection->avg(function ($user) {
    assertType('User', $user);

    return 0.1;
}));

assertType('bool', $collection->contains(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->contains('string', '=', 'string'));

assertType('Illuminate\Support\Collection<int, array<int, string|User>>', $collection->crossJoin($collection::make(['string'])));
assertType('Illuminate\Support\Collection<int, array<int, int|User>>', $collection->crossJoin([1, 2]));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diff([1, 2]));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diff(['string-2']));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffUsing([1, 2], function ($int) {
    assertType('int', $int);

    return -1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diffUsing(['string-2'], function ($string) {
    assertType('string', $string);

    return -1;
}));

assertType('array<int, User>', $collection->all());

assertType('User|null', $collection->get(0));
assertType('string|User', $collection->get(0, 'string'));

assertType('User|null', $collection->first());
assertType('User|null', $collection->first(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('string|User', $collection->first(function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));

assertType('array<int, User>', $collection->toArray());
assertType('array<string, string>', collect(['string' => 'string'])->toArray());
assertType('array<int, int>', collect([1, 2])->toArray());
