<?php

use function PHPStan\Testing\assertType;

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Database\Eloquent\Casts\ArrayObject<(int|string), mixed>, iterable>',
    \Illuminate\Database\Eloquent\Casts\AsArrayObject::castUsing([]),
);

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Support\Collection<(int|string), mixed>, iterable>',
    \Illuminate\Database\Eloquent\Casts\AsCollection::castUsing([]),
);

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Database\Eloquent\Casts\ArrayObject<(int|string), mixed>, iterable>',
    \Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject::castUsing([]),
);

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Support\Collection<(int|string), mixed>, iterable>',
    \Illuminate\Database\Eloquent\Casts\AsEncryptedCollection::castUsing([]),
);

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Database\Eloquent\Casts\ArrayObject<(int|string), UserType>, iterable<UserType>>',
    \Illuminate\Database\Eloquent\Casts\AsEnumArrayObject::castUsing([\UserType::class]),
);

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Support\Collection<(int|string), UserType>, iterable<UserType>>',
    \Illuminate\Database\Eloquent\Casts\AsEnumCollection::castUsing([\UserType::class]),
);

assertType(
    'Illuminate\Contracts\Database\Eloquent\CastsAttributes<Illuminate\Support\Stringable, string|Stringable>',
    \Illuminate\Database\Eloquent\Casts\AsStringable::castUsing([]),
);
