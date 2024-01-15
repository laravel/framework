<?php

namespace Illuminate\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Runner\Version;

if (str_starts_with(Version::series(), '10')) {
    class CountInDatabase extends Constraint
    {
        use Concerns\CountInDatabase;
    }
} else {
    readonly class CountInDatabase extends Constraint
    {
        use Concerns\CountInDatabase;
    }
}
