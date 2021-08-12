<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use function PHPStan\Testing\assertType;

class User extends Authenticatable
{
}

$collection = collect([new User]);
/** @var Arrayable<int, User> $arrayable */
$arrayable = [];
/** @var iterable<int, int> $iterable */
$iterable = [];
/** @var Traversable<int, string> $traversable */
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

assertType('Illuminate\Support\Collection<int, User>', $collection::times(10, function () {
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

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffAssoc([1, 2]));
assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])->diffAssoc(['string' => 'string']));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffAssocUsing([1, 2], function ($int) {
    assertType('int', $int);

    return -1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diffAssocUsing(['string-2'], function ($int) {
    assertType('int', $int);

    return -1;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffKeys([1, 2]));
assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])->diffKeys(['string' => 'string']));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffKeysUsing([1, 2], function ($int) {
    assertType('int', $int);

    return -1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diffKeysUsing(['string-2'], function ($int) {
    assertType('int', $int);

    return -1;
}));

assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])
    ->duplicates());
assertType('Illuminate\Support\Collection<int, User>', $collection->duplicates('name', true));
assertType('Illuminate\Support\Collection<int, int|string>', $collection::make([3, 'string'])
    ->duplicates(function ($intOrString) {
        assertType('int|string', $intOrString);

        return true;
    }));

assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])
    ->duplicatesStrict());
assertType('Illuminate\Support\Collection<int, User>', $collection->duplicatesStrict('name'));
assertType('Illuminate\Support\Collection<int, int|string>', $collection::make([3, 'string'])
    ->duplicatesStrict(function ($intOrString) {
        assertType('int|string', $intOrString);

        return true;
    }));

assertType('Illuminate\Support\Collection<int, User>', $collection->each(function ($user) {
    assertType('User', $user);

    return null;
}));
assertType('Illuminate\Support\Collection<int, User>', $collection->each(function ($user) {
    assertType('User', $user);
}));

assertType('Illuminate\Support\Collection<int, array(string)>', $collection::make([['string']])
    ->eachSpread(function ($int, $string) {
        // assertType('int', $int);
        // assertType('int', $string);

        return null;
    }));
assertType('Illuminate\Support\Collection<int, array(int, string)>', $collection::make([[1, 'string']])
    ->eachSpread(function ($int, $string) {
        // assertType('int', $int);
        // assertType('int', $string);
    }));

assertType('bool', $collection->every(function ($user, $int) {
    assertType('int', $int);
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->every('string', '=', 'string'));

assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])->except(['string']));
assertType('Illuminate\Support\Collection<int, User>', $collection->except([1]));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string'])
    ->except($collection->keys()->toArray()));

assertType('Illuminate\Support\Collection<int, User>', $collection->filter());
assertType('Illuminate\Support\Collection<int, User>', $collection->filter(function ($user) {
    assertType('User', $user);

    return true;
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->filter());
assertType('Illuminate\Support\Collection<int, User>', $collection->filter(function ($user) {
    assertType('User', $user);

    return true;
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->when(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|void', $collection->when(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->when(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->whenEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|void', $collection->whenEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->whenEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->whenNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|void', $collection->whenNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->whenNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->unless(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|void', $collection->unless(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->unless(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->unlessEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|void', $collection->unlessEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->unlessEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->unlessNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|void', $collection->unlessNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->unlessNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType("Illuminate\Support\Collection<int, array('string' => string)>", $collection::make([['string' => 'string']])
    ->where('string'));
assertType("Illuminate\Support\Collection<int, array('string' => string)>", $collection::make([['string' => 'string']])
    ->where('string', '=', 'string'));
assertType("Illuminate\Support\Collection<int, array('string' => string)>", $collection::make([['string' => 'string']])
    ->where('string', 'string'));

assertType('Illuminate\Support\Collection<int, User>', $collection->whereNull());
assertType('Illuminate\Support\Collection<int, User>', $collection->whereNull('foo'));

assertType('Illuminate\Support\Collection<int, User>', $collection->whereNotNull());
assertType('Illuminate\Support\Collection<int, User>', $collection->whereNotNull('foo'));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereStrict('string', 2));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereIn('string', [2]));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereInStrict('string', [2]));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereBetween('string', [1, 3]));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereNotBetween('string', [1, 3]));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereNotIn('string', [2]));

assertType("Illuminate\Support\Collection<int, array('string' => int)>", $collection::make([['string' => 2]])
    ->whereNotInStrict('string', [2]));

assertType('Illuminate\Support\Collection<int, int|User>', $collection::make([new User, 1])
    ->whereInstanceOf(User::class));

assertType('Illuminate\Support\Collection<int, int|User>', $collection::make([new User, 1])
    ->whereInstanceOf([User::class, User::class]));

assertType('User|null', $collection->first());
assertType('User|null', $collection->first(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('string|User', $collection->first(function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));

assertType('User|null', $collection->firstWhere('string', 'string'));
assertType('User|null', $collection->firstWhere('string', 'string', 'string'));

assertType('Illuminate\Support\Collection<string, int>', $collection::make(['string'])->flip());

assertType('Illuminate\Support\Collection<(int|string), array<User>>', $collection->groupBy('name'));
assertType('Illuminate\Support\Collection<(int|string), array<User>>', $collection->groupBy('name', true));
assertType('Illuminate\Support\Collection<(int|string), array<User>>', $collection->groupBy(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 'foo';
}));
assertType('Illuminate\Support\Collection<(int|string), array<User>>', $collection->groupBy(function ($user) {
    return 'foo';
}));

assertType('Illuminate\Support\Collection<(int|string), array<User>>', $collection->keyBy('name'));
assertType('Illuminate\Support\Collection<(int|string), array<User>>', $collection->keyBy(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 'foo';
}));

assertType('bool', $collection->has(0));
assertType('bool', $collection->has([0, 1]));

assertType('Illuminate\Support\Collection<int, User>', $collection->intersect([new User]));

assertType('Illuminate\Support\Collection<int, User>', $collection->intersectByKeys([new User]));

assertType('Illuminate\Support\Collection<int, int>', $collection->keys());

assertType('User|null', $collection->last());
assertType('User|null', $collection->last(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));
assertType('string|User', $collection->last(function () {
    return true;
}, 'string'));

assertType('Illuminate\Support\Collection<int, int>', $collection->map(function () {
    return 1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection->map(function () {
    return 'string';
}));

assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string'])
    ->map(function ($string, $int) {
        assertType('string', $string);
        assertType('int', $int);

        return (string) $string;
    }));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->push(2));

assertType('array<int, User>', $collection->all());

assertType('User|null', $collection->get(0));
assertType('string|User', $collection->get(0, 'string'));

assertType('array<int, User>', $collection->toArray());
assertType('array<string, string>', collect(['string' => 'string'])->toArray());
assertType('array<int, int>', collect([1, 2])->toArray());
