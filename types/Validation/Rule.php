<?php

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\ExistsUsingBuilder;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rules\UniqueUsingBuilder;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Database\Query\Builder $builder */
assertType(Unique::class, Rule::unique('table', 'id'));
assertType(UniqueUsingBuilder::class, Rule::unique($builder, 'id'));

assertType(Exists::class, Rule::exists('table', 'id'));
assertType(ExistsUsingBuilder::class, Rule::exists($builder, 'id'));
