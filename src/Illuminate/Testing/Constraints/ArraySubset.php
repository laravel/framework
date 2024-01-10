<?php

namespace Illuminate\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Runner\Version;

if (str_starts_with(Version::series(), '10')) {
    class ArraySubset extends Constraint
    {
        use Concerns\ArraySubset;
    }
} else {
    readonly class ArraySubset extends Constraint
    {
        use Concerns\ArraySubset;
    }
}
