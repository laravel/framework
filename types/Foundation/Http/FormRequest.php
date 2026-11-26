<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

$request = new FormRequest;

assertType('User|null', $request->model('user_id', User::class));
assertType('User|null', $request->model('user_id', User::class, function (Builder $query) {
    $query->where('active', true);
}));
assertType('Illuminate\Database\Eloquent\Model|null', $request->model('user_id'));

assertType('User', $request->modelOrFail('user_id', User::class));
assertType('Illuminate\Database\Eloquent\Model', $request->modelOrFail('user_id'));

assertType('Illuminate\Database\Eloquent\Collection<int, User>', $request->models('user_ids', User::class));
assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Model>', $request->models('user_ids'));
