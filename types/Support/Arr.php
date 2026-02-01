<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use JsonSerializable;
use Traversable;

use function PHPStan\Testing\assertType;

$array = [new User];
/** @var iterable<int, User> $iterable */
$iterable = [];
/** @var Traversable<int, User> $traversable */
$traversable = new ArrayIterator([new User]);

assertType('User|null', Arr::first($array));
assertType('User|null', Arr::first($array, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::first($array, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::first($array, null, function () {
    return 'string';
}));

assertType('User|null', Arr::first($iterable));
assertType('User|null', Arr::first($iterable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::first($iterable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::first($iterable, null, function () {
    return 'string';
}));

assertType('User|null', Arr::first($traversable));
assertType('User|null', Arr::first($traversable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::first($traversable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::first($traversable, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($array));
assertType('User|null', Arr::last($array, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::last($array, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::last($array, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($iterable));
assertType('User|null', Arr::last($iterable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::last($iterable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::last($iterable, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($traversable));
assertType('User|null', Arr::last($traversable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::last($traversable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::last($traversable, null, function () {
    return 'string';
}));

assertType("array{array<'a'|'b'>, array<1|2>}", Arr::divide(['a' => 1, 'b' => 2]));
assertType('array{array<0>, array<1>}', Arr::divide([1]));

/**
 * @return iterable<int>
 */
function generateArray(): iterable
{
    yield 1;
}
assertType('true', Arr::arrayable([]));
assertType('true', Arr::arrayable(new class implements Arrayable
{
    public function toArray()
    {
        return [];
    }
}));
assertType('true', Arr::arrayable(new class implements Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }
}));
assertType('true', Arr::arrayable(generateArray()));
assertType('true', Arr::arrayable(new class implements JsonSerializable
{
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return '{"foo":"bar"}';
    }
}));
assertType('false', Arr::arrayable(1));

assertType('array<int, array<1|2|3>>', Arr::crossJoin([1], [2], ['a' => 3]));

/** @phpstan-ignore staticMethod.impossibleType , staticMethod.alreadyNarrowedType */
assertType('false', Arr::isAssoc([1]));

/** @phpstan-ignore staticMethod.impossibleType , staticMethod.alreadyNarrowedType */
assertType('true', Arr::isAssoc(['a' => 1]));

/** @phpstan-ignore staticMethod.impossibleType , staticMethod.alreadyNarrowedType */
assertType('true', Arr::isList([1]));

/** @phpstan-ignore staticMethod.impossibleType , staticMethod.alreadyNarrowedType */
assertType('false', Arr::isList(['a' => 1]));

assertType('array<0|1|2, 1|2|3>', Arr::sort([1, 3, 2]));
assertType("array<'a'|'b'|'c', 1|2|3>", Arr::sort(['a' => 1, 'c' => 3, 'b' => 2]));
assertType('array<0|1|2, 1|2|3>', Arr::sortDesc([1, 3, 2]));
assertType("array<'a'|'b'|'c', 1|2|3>", Arr::sortDesc(['a' => 1, 'c' => 3, 'b' => 2]));
assertType('array<0|1|2, 1|2|3>', Arr::sortRecursive([1, 3, 2]));
assertType("array<'a'|'b'|'c', 1|2|3>", Arr::sortRecursive(['a' => 1, 'c' => 3, 'b' => 2]));
assertType('array<0|1|2, 1|2|3>', Arr::sortRecursiveDesc([1, 3, 2]));
assertType("array<'a'|'b'|'c', 1|2|3>", Arr::sortRecursiveDesc(['a' => 1, 'c' => 3, 'b' => 2]));

assertType('\'\'', Arr::toCssClasses(['hidden' => false]));
assertType('\'\'', Arr::toCssClasses([]));
assertType('\'\'', Arr::toCssClasses(''));
assertType('non-empty-string', Arr::toCssClasses(['hidden' => true]));
assertType('non-empty-string', Arr::toCssClasses(['hidden']));
assertType('non-empty-string', Arr::toCssClasses('hidden'));

assertType('\'\'', Arr::toCssStyles(['background: red' => false]));
assertType('\'\'', Arr::toCssStyles([]));
assertType('\'\'', Arr::toCssStyles(''));
assertType('non-empty-string', Arr::toCssStyles(['background: red' => true]));
assertType('non-empty-string', Arr::toCssStyles(['background: red']));
assertType('non-empty-string', Arr::toCssStyles('background: red'));

assertType('array{}', Arr::wrap(null));
assertType('array{1}', Arr::wrap(1));
assertType('array{1}', Arr::wrap([1]));
