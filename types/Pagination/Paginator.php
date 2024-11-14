<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use function PHPStan\Testing\assertType;

$items = [new Post(), new Post(), new Post()];

/** @var Paginator<int, Post> $paginator */
$paginator = new Paginator($items, 1, 1);

assertType('Post[]', $paginator->items());
assertType('array<int, Post>', $paginator->toArray());
assertType('ArrayIterator<TKey, TValue>', $paginator->getIterator());

$paginator->each(function ($post) {
    assertType('Post', $post);
});

foreach ($paginator as $post) {
    assertType('Post', $post);
}

/** @var LengthAwarePaginator<int, Post> $lengthAwarePaginator */
$lengthAwarePaginator = new LengthAwarePaginator($items, 1, 1);

assertType('Post[]', $lengthAwarePaginator->items());
assertType('array<int, Post>', $lengthAwarePaginator->toArray());
assertType('ArrayIterator<int, Post>', $lengthAwarePaginator->getIterator());

$lengthAwarePaginator->each(function ($post) {
    assertType('Post', $post);
});

foreach ($lengthAwarePaginator as $post) {
    assertType('Post', $post);
}
