<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Support\Arr;

class MatrixSequence extends Sequence
{
    /**
     * Create a new matrix sequence instance.
     *
     * @param  array  $sequences
     * @return void
     */
    public function __construct(...$sequences)
    {
        $matrix = array_map(
            function ($a) {
                return array_merge(...$a);
            },
            Arr::crossJoin(...$sequences),
        );

        parent::__construct(...$matrix);
    }
}
