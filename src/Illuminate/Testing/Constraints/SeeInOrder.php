<?php

namespace Illuminate\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Runner\Version;

if (str_starts_with(Version::series(), '10')) {
    class SeeInOrder extends Constraint
    {
        use Concerns\SeeInOrder;
    }
} else {
    readonly class SeeInOrder extends Constraint
    {
        use Concerns\SeeInOrder;
    }
}
