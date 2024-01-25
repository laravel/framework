<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

$collection = collect([new User]);
/** @var Arrayable<int, User> $arrayable */
$arrayable = [];
/** @var iterable<int, int> $iterable */
$iterable = [];
/** @var Traversable<int, string> $traversable */
$traversable = [];

class Invokable
{
    public function __invoke(): string
    {
        return 'Taylor';
    }
}
$invokable = new Invokable();

assertType('Illuminate\Support\Collection<int, User>', $collection);

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

assertType('Illuminate\Support\Collection<(int|string), string>', $collection->wrap('string'));
assertType('Illuminate\Support\Collection<(int|string), User>', $collection->wrap(new User));

assertType('Illuminate\Support\Collection<(int|string), string>', $collection->wrap(['string']));
assertType('Illuminate\Support\Collection<(int|string), User>', $collection->wrap(['string' => new User]));

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
assertType('bool', $collection::make([[1]])->containsStrict(0));

assertType('Illuminate\Support\LazyCollection<int, User>', $collection->lazy());

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
assertType('bool', $collection->contains(function ($user, $int) {
    assertType('int', $int);
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->contains('string', '=', 'string'));

assertType('Illuminate\Support\Collection<int, array<int, string|User>>', $collection->crossJoin($collection::make(['string'])));
assertType('Illuminate\Support\Collection<int, array<int, int|User>>', $collection->crossJoin([1, 2]));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diff([1, 2]));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diff(['string-2']));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffUsing([1, 2], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diffUsing(['string-2'], function ($stringA, $stringB) {
    assertType('string', $stringA);
    assertType('string', $stringB);

    return -1;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffAssoc([1, 2]));
assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])->diffAssoc(['string' => 'string']));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffAssocUsing([1, 2], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diffAssocUsing(['string-2'], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffKeys([1, 2]));
assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])->diffKeys(['string' => 'string']));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([3, 4])->diffKeysUsing([1, 2], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string-1'])->diffKeysUsing(['string-2'], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

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
assertType('Illuminate\Support\Collection<int, User>', $collection->each(function ($user, $int) {
    assertType('int', $int);
    assertType('User', $user);
}));

assertType('Illuminate\Support\Collection<int, array{string}>', $collection::make([['string']])
    ->eachSpread(function ($int, $string) {
        // assertType('int', $int);
        // assertType('int', $string);

        return null;
    }));
assertType('Illuminate\Support\Collection<int, array{int, string}>', $collection::make([[1, 'string']])
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
    ->except([1]));

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
assertType('Illuminate\Support\Collection<int, User>|null', $collection->when(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->when(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));
assertType('Illuminate\Support\Collection<int, User>|null', $collection->when('Taylor', function ($collection, $name) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
    assertType('string', $name);
}));
assertType(
    'Illuminate\Support\Collection<int, User>|null',
    $collection->when(
        'Taylor',
        function ($collection, $name) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('string', $name);
        },
        function ($collection, $name) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('string', $name);
        }
    )
);
assertType('Illuminate\Support\Collection<int, User>|null', $collection->when(fn () => 'Taylor', function ($collection, $name) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
    assertType('string', $name);
}));
assertType(
    'Illuminate\Support\Collection<int, User>|null',
    $collection->when(
        function ($collection) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);

            return 14;
        },
        function ($collection, $count) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('int', $count);
        },
        function ($collection, $count) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('int', $count);
        }
    )
);

assertType('Illuminate\Support\Collection<int, User>|null', $collection->when($invokable, function ($collection, $param) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
    assertType('Invokable', $param);
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->whenEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|null', $collection->whenEmpty(function ($collection) {
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
assertType('Illuminate\Support\Collection<int, User>|null', $collection->whenNotEmpty(function ($collection) {
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
assertType('Illuminate\Support\Collection<int, User>|null', $collection->unless(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->unless(true, function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));
assertType('Illuminate\Support\Collection<int, User>|null', $collection->unless('Taylor', function ($collection, $name) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
    assertType('string', $name);
}));
assertType(
    'Illuminate\Support\Collection<int, User>|null',
    $collection->unless(
        'Taylor',
        function ($collection, $name) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('string', $name);
        },
        function ($collection, $name) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('string', $name);
        }
    )
);
assertType('Illuminate\Support\Collection<int, User>|null', $collection->unless(fn () => 'Taylor', function ($collection, $name) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
    assertType('string', $name);
}));
assertType(
    'Illuminate\Support\Collection<int, User>|null',
    $collection->unless(
        function ($collection) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);

            return 14;
        },
        function ($collection, $count) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('int', $count);
        },
        function ($collection, $count) {
            assertType('Illuminate\Support\Collection<int, User>', $collection);
            assertType('int', $count);
        }
    )
);

assertType('Illuminate\Support\Collection<int, User>|null', $collection->unless($invokable, function ($collection, $param) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
    assertType('Invokable', $param);
}));

assertType('bool|Illuminate\Support\Collection<int, User>', $collection->unlessEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>|null', $collection->unlessEmpty(function ($collection) {
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
assertType('Illuminate\Support\Collection<int, User>|null', $collection->unlessNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));
assertType('Illuminate\Support\Collection<int, User>|string', $collection->unlessNotEmpty(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType("Illuminate\Support\Collection<int, array{string: string}>", $collection::make([['string' => 'string']])
    ->where('string'));
assertType("Illuminate\Support\Collection<int, array{string: string}>", $collection::make([['string' => 'string']])
    ->where('string', '=', 'string'));
assertType("Illuminate\Support\Collection<int, array{string: string}>", $collection::make([['string' => 'string']])
    ->where('string', 'string'));

assertType('Illuminate\Support\Collection<int, User>', $collection->whereNull());
assertType('Illuminate\Support\Collection<int, User>', $collection->whereNull('foo'));

assertType('Illuminate\Support\Collection<int, User>', $collection->whereNotNull());
assertType('Illuminate\Support\Collection<int, User>', $collection->whereNotNull('foo'));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereStrict('string', 2));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereIn('string', [2]));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereInStrict('string', [2]));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereBetween('string', [1, 3]));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereNotBetween('string', [1, 3]));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereNotIn('string', [2]));

assertType("Illuminate\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereNotInStrict('string', [2]));

assertType('Illuminate\Support\Collection<int, User>', $collection::make([new User, 1])
    ->whereInstanceOf(User::class));

assertType('Illuminate\Support\Collection<int, Exception|User>', $collection::make([new User, 1])
    ->whereInstanceOf([User::class, Exception::class]));

assertType('User|null', $collection->first());
assertType('User|null', $collection->first(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('string|User', $collection->first(function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType('string|User', $collection->first(null, function () {
    return 'string';
}));

assertType('Illuminate\Support\Collection<int, mixed>', $collection->flatten());
assertType('Illuminate\Support\Collection<int, mixed>', $collection::make(['string' => 'string'])->flatten(4));

assertType('User|null', $collection->firstWhere('string', 'string'));
assertType('User|null', $collection->firstWhere('string', 'string', 'string'));

assertType('User|null', $collection->value('string'));
assertType('string|User', $collection->value('string', 'string'));
assertType('string|User', $collection->value('string', fn () => 'string'));

assertType('Illuminate\Support\Collection<string, int>', $collection::make(['string'])->flip());

assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), User>>', $collection->groupBy('name'));
assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), User>>', $collection->groupBy('name', true));
assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), User>>', $collection->groupBy(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 'foo';
}));
assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), User>>', $collection->groupBy(function ($user) {
    return 'foo';
}));

assertType('Illuminate\Support\Collection<(int|string), User>', $collection->keyBy('name'));
assertType('Illuminate\Support\Collection<(int|string), User>', $collection->keyBy(function ($user, $int) {
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
assertType('string|User', $collection->last(null, function () {
    return 'string';
}));

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

assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string'])
    ->mapSpread(function () {
        return 'string';
    }));

assertType('Illuminate\Support\Collection<int, int>', $collection::make(['string'])
    ->mapSpread(function () {
        return 1;
    }));

assertType('Illuminate\Support\Collection<string, array<int, int>>', $collection::make(['string', 'string'])
    ->mapToDictionary(function ($stringValue, $stringKey) {
        assertType('string', $stringValue);
        assertType('int', $stringKey);

        return ['string' => 1];
    }));

assertType('Illuminate\Support\Collection<string, Illuminate\Support\Collection<int, int>>', $collection::make(['string', 'string'])
    ->mapToGroups(function ($stringValue, $stringKey) {
        assertType('string', $stringValue);
        assertType('int', $stringKey);

        return ['string' => 1];
    }));

assertType('Illuminate\Support\Collection<string, int>', $collection::make(['string'])
    ->mapWithKeys(function ($string, $int) {
        assertType('string', $string);
        assertType('int', $int);

        return ['string' => 1];
    }));

assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string'])
    ->flatMap(function ($string, $int) {
        assertType('string', $string);
        assertType('int', $int);

        return [0 => 'string'];
    }));

assertType('Illuminate\Support\Collection<int, User>', $collection->mapInto(User::class));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->merge([2]));
assertType('Illuminate\Support\Collection<int, string>', $collection->make(['string'])->merge(['string']));

assertType('Illuminate\Support\Collection<int, int|string>', $collection->make([1])->mergeRecursive([2 => 'string']));
assertType('Illuminate\Support\Collection<int, string>', $collection->make(['string'])->mergeRecursive(['string']));

assertType('Illuminate\Support\Collection<string, int>', $collection->make(['string' => 'string'])->combine([2]));
assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->combine([1]));
assertType('Illuminate\Support\Collection<string, string>', $collection->make(['string'])->combine(['string']));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->union([1]));
assertType('Illuminate\Support\Collection<string, string>', $collection->make(['string' => 'string'])->union(['string' => 'string']));

assertType('mixed', $collection->make()->min());
assertType('mixed', $collection->make([1])->min());
assertType('mixed', $collection->make([1])->min('string'));
assertType('mixed', $collection->make(['string' => 1])->min('string'));
assertType('mixed', $collection->make([1])->min(function ($int) {
    assertType('int', $int);

    return 1;
}));
assertType('mixed', $collection->make([new User])->min('id'));

assertType('mixed', $collection->make()->max());
assertType('mixed', $collection->make([1])->max());
assertType('mixed', $collection->make([1])->max('string'));
assertType('mixed', $collection->make([1])->max(function ($int) {
    assertType('int', $int);

    return 1;
}));
assertType('mixed', $collection->make([new User])->max('id'));

assertType('Illuminate\Support\Collection<int, User>', $collection->nth(1, 2));

assertType('Illuminate\Support\Collection<string, string>', $collection::make(['string' => 'string'])->only(['string']));
assertType('Illuminate\Support\Collection<int, User>', $collection->only([1]));
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string'])
    ->only([1]));

assertType('Illuminate\Support\Collection<int, User>', $collection->forPage(1, 2));

assertType('Illuminate\Support\Collection<int<0, 1>, Illuminate\Support\Collection<int, User>>', $collection->partition(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));
assertType('Illuminate\Support\Collection<int<0, 1>, Illuminate\Support\Collection<int, string>>', $collection::make(['string'])->partition('string', '=', 'string'));
assertType('Illuminate\Support\Collection<int<0, 1>, Illuminate\Support\Collection<int, string>>', $collection::make(['string'])->partition('string', 'string'));
assertType('Illuminate\Support\Collection<int<0, 1>, Illuminate\Support\Collection<int, string>>', $collection::make(['string'])->partition('string'));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->concat([2]));
assertType('Illuminate\Support\Collection<int, string>', $collection->make(['string'])->concat(['string']));

assertType('Illuminate\Support\Collection<int, int>|int', $collection->make([1])->random(2));
assertType('Illuminate\Support\Collection<int, string>|string', $collection->make(['string'])->random());

assertType('int', $collection
    ->reduce(function ($null, $user) {
        assertType('User', $user);
        assertType('int|null', $null);

        return 1;
    }));
assertType('int', $collection
    ->reduce(function ($int, $user) {
        assertType('User', $user);
        assertType('int', $int);

        return 1;
    }, 0));
assertType('int', $collection
    ->reduce(function ($int, $user, $key) {
        assertType('User', $user);
        assertType('int', $int);
        assertType('int', $key);

        return 1;
    }, 0));

assertType('int', $collection
    ->reduceWithKeys(function ($null, $user) {
        assertType('User', $user);
        assertType('int|null', $null);

        return 1;
    }));
assertType('int', $collection
    ->reduceWithKeys(function ($int, $user) {
        assertType('User', $user);
        assertType('int', $int);

        return 1;
    }, 0));
assertType('int', $collection
    ->reduceWithKeys(function ($int, $user, $key) {
        assertType('User', $user);
        assertType('int', $int);
        assertType('int', $key);

        return 1;
    }, 0));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([1])->replace([1]));
assertType('Illuminate\Support\Collection<int, User>', $collection->replace([new User]));

assertType('Illuminate\Support\Collection<int, int>', $collection::make([1])->replaceRecursive([1]));
assertType('Illuminate\Support\Collection<int, User>', $collection->replaceRecursive([new User]));

assertType('Illuminate\Support\Collection<int, User>', $collection->reverse());

// assertType('int|bool', $collection->make([1])->search(2));
// assertType('string|bool', $collection->make(['string' => 'string'])->search('string'));
// assertType('int|bool', $collection->search(function ($user, $int) {
//     assertType('User', $user);
//    assertType('int', $int);
//
//    return true;
// }));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->shuffle());
assertType('Illuminate\Support\Collection<int, User>', $collection->shuffle());

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->skip(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->skip(1));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->skipUntil(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->skipUntil(new User));
assertType('Illuminate\Support\Collection<int, User>', $collection->skipUntil(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->skipWhile(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->skipWhile(new User));
assertType('Illuminate\Support\Collection<int, User>', $collection->skipWhile(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->slice(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->slice(1, 2));

assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, User>>', $collection->split(3));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, int>>', $collection->make([1])->split(3));

assertType('string', $collection->make(['string' => 'string'])->sole('string', 'string'));
assertType('string', $collection->make(['string' => 'string'])->sole('string', '=', 'string'));
assertType('User', $collection->sole(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('User', $collection->firstOrFail());
assertType('User', $collection->firstOrFail('string', 'string'));
assertType('User', $collection->firstOrFail('string', '=', 'string'));
assertType('User', $collection->firstOrFail(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, string>>', $collection::make(['string'])->chunk(1));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, User>>', $collection->chunk(2));

assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, User>>', $collection->chunkWhile(function ($user, $int, $collection) {
    assertType('User', $user);
    assertType('int', $int);
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return true;
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->sort(function ($userA, $userB) {
    assertType('User', $userA);
    assertType('User', $userB);

    return 1;
}));
assertType('Illuminate\Support\Collection<int, User>', $collection->sort());

assertType('Illuminate\Support\Collection<int, User>', $collection->sortDesc());
assertType('Illuminate\Support\Collection<int, User>', $collection->sortDesc(2));

assertType('Illuminate\Support\Collection<int, User>', $collection->sortBy(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortBy('string'));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortBy('string', 1, false));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortBy([
    ['string', 'string'],
]));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortBy([function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}]));

assertType('Illuminate\Support\Collection<int, User>', $collection->sortByDesc(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortByDesc('string'));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortByDesc('string', 1));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortByDesc([
    ['string', 'string'],
]));
assertType('Illuminate\Support\Collection<int, User>', $collection->sortByDesc([function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}]));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->sortKeys());
assertType('Illuminate\Support\Collection<string, string>', $collection->make(['string' => 'string'])->sortKeys(1, true));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->sortKeysDesc());
assertType('Illuminate\Support\Collection<string, string>', $collection->make(['string' => 'string'])->sortKeysDesc(1));

assertType('mixed', $collection->make([1])->sum('string'));
assertType('mixed', $collection->make(['string'])->sum(function ($string) {
    assertType('string', $string);

    return 1;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->take(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->take(1));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->takeUntil(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->takeUntil(new User));
assertType('Illuminate\Support\Collection<int, User>', $collection->takeUntil(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->takeWhile(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->takeWhile(new User));
assertType('Illuminate\Support\Collection<int, User>', $collection->takeWhile(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->tap(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);
}));

assertType('Illuminate\Support\Collection<int, int>', $collection->pipe(function ($collection) {
    assertType('Illuminate\Support\Collection<int, User>', $collection);

    return collect([1]);
}));
assertType('int', $collection->make([1])->pipe(function ($collection) {
    assertType('Illuminate\Support\Collection<int, int>', $collection);

    return 1;
}));

assertType('User', $collection->pipeInto(User::class));

assertType('Illuminate\Support\Collection<(int|string), mixed>', $collection->make(['string' => 'string'])->pluck('string'));
assertType('Illuminate\Support\Collection<(int|string), mixed>', $collection->make(['string' => 'string'])->pluck('string', 'string'));

assertType('Illuminate\Support\Collection<int, User>', $collection->reject());
assertType('Illuminate\Support\Collection<int, User>', $collection->reject(new User));
assertType('Illuminate\Support\Collection<int, User>', $collection->reject(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('Illuminate\Support\Collection<int, User>', $collection->reject(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->unique());
assertType('Illuminate\Support\Collection<int, User>', $collection->unique(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return $user->getTable();
}));
assertType('Illuminate\Support\Collection<string, string>', $collection->make(['string' => 'string'])->unique(function ($stringA, $stringB) {
    assertType('string', $stringA);
    assertType('string', $stringB);

    return $stringA;
}, true));

assertType('Illuminate\Support\Collection<int, User>', $collection->uniqueStrict());
assertType('Illuminate\Support\Collection<int, User>', $collection->uniqueStrict(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return $user->getTable();
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->values());
assertType('Illuminate\Support\Collection<int, string>', $collection::make(['string', 'string'])->values());
assertType('Illuminate\Support\Collection<int, int|string>', $collection::make(['string', 1])->values());

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->pad(2, 0));
assertType('Illuminate\Support\Collection<int, int|string>', $collection->make([1])->pad(2, 'string'));
assertType('Illuminate\Support\Collection<int, int|User>', $collection->pad(2, 0));

assertType('Illuminate\Support\Collection<(int|string), int>', $collection->make([1])->countBy());
assertType('Illuminate\Support\Collection<(int|string), int>', $collection->make(['string' => 'string'])->countBy('string'));
assertType('Illuminate\Support\Collection<(int|string), int>', $collection->make([new User])->countBy('email'));
assertType('Illuminate\Support\Collection<(int|string), int>', $collection->make(['string'])->countBy(function ($string, $int) {
    assertType('string', $string);
    assertType('int', $int);

    return $string;
}));

assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, int|User>>', $collection->zip([1]));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, string|User>>', $collection->zip(['string']));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, string>>', $collection::make(['string' => 'string'])->zip(['string']));

assertType('Illuminate\Support\Collection<int, User>', $collection->collect());
assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->collect());

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->push(2));

assertType('array<int, User>', $collection->all());

assertType('User|null', $collection->get(0));
assertType('string|User', $collection->get(0, 'string'));
assertType('string|User', $collection->get(0, function () {
    return 'string';
}));

assertType('string|User', $collection->getOrPut(0, 'string'));
assertType('string|User', $collection->getOrPut(0, fn () => 'string'));

assertType('Illuminate\Support\Collection<int, User>', $collection->forget(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->forget([1, 2]));

assertType('Illuminate\Support\Collection<int, User>|User|null', $collection->pop(1));
assertType('Illuminate\Support\Collection<int, string>|string|null', $collection::make([
    'string-key-1' => 'string-value-1',
    'string-key-2' => 'string-value-2',
])->pop(2));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->prepend(2));
assertType('Illuminate\Support\Collection<int, User>', $collection->prepend(new User, 2));

assertType('Illuminate\Support\Collection<int, int>', $collection->make([1])->push(2));
assertType('Illuminate\Support\Collection<int, User>', $collection->push(new User, new User));

assertType('User|null', $collection->pull(1));
assertType('string|User', $collection->pull(1, 'string'));
assertType('string|User', $collection->pull(1, function () {
    return 'string';
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->put(1, new User));
assertType('Illuminate\Support\Collection<string, string>', $collection::make([
    'string-key-1' => 'string-value-1',
])->put('string-key-2', 'string-value-2'));

assertType('Illuminate\Support\Collection<int, User>|User|null', $collection->shift(1));
assertType('Illuminate\Support\Collection<int, string>|string|null', $collection::make([
    'string-key-1' => 'string-value-1',
    'string-key-2' => 'string-value-2',
])->shift(2));

assertType(
    'Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, User>>',
    $collection->sliding(2)
);

assertType(
    'Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, string>>',
    $collection::make(['string' => 'string'])->sliding(2, 1)
);

assertType(
    'Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, User>>',
    $collection->splitIn(2)
);

assertType(
    'Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, string>>',
    $collection::make(['string' => 'string'])->splitIn(1)
);

assertType('Illuminate\Support\Collection<int, User>', $collection->splice(1));
assertType('Illuminate\Support\Collection<int, User>', $collection->splice(1, 1, [new User]));

assertType('Illuminate\Support\Collection<int, User>', $collection->transform(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return new User;
}));

assertType('Illuminate\Support\Collection<int, User>', $collection->add(new User));

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class CustomCollection extends Collection
{
}

// assertType('CustomCollection<int, User>', CustomCollection::make([new User]));
assertType('Illuminate\Support\Collection<int, User>', CustomCollection::make([new User])->toBase());

assertType('bool', $collection->offsetExists(0));
assertType('bool', isset($collection[0]));

$collection->offsetSet(0, new User);
$collection->offsetSet(null, new User);
assertType('User', $collection[0] = new User);

$collection->offsetUnset(0);
unset($collection[0]);

assertType('array<int, mixed>', $collection->toArray());
assertType('array<string, mixed>', collect(['string' => 'string'])->toArray());
assertType('array<int, mixed>', collect([1, 2])->toArray());

assertType('ArrayIterator<int, User>', $collection->getIterator());
foreach ($collection as $int => $user) {
    assertType('int', $int);
    assertType('User', $user);
}

class Animal
{
}
class Tiger extends Animal
{
}
class Lion extends Animal
{
}
class Zebra extends Animal
{
}

class Zoo
{
    /**
     * @var \Illuminate\Support\Collection<int, Animal>
     */
    private Collection $animals;

    public function __construct()
    {
        $this->animals = collect([
            new Tiger,
            new Lion,
            new Zebra,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Animal>
     */
    public function getWithoutZebras(): Collection
    {
        return $this->animals->filter(fn (Animal $animal) => ! $animal instanceof Zebra);
    }
}

$zoo = new Zoo();

assertType('Illuminate\Support\Collection<int, Animal>', $zoo->getWithoutZebras());
