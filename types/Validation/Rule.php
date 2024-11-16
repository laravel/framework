<?php

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\ExistsUsingBuilder;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Database\Query\Builder $builder */
assertType(Exists::class, Rule::exists('table', 'id'));
assertType(ExistsUsingBuilder::class, Rule::exists($builder, 'id'));
