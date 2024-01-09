<?php

namespace Illuminate\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Runner\Version;

if (str_starts_with(Version::series(), '10')) {
    class HasInDatabase extends Constraint
    {
        use Concerns\HasInDatabase;
    }
} else {
    readonly class HasInDatabase extends Constraint
    {
        use Concerns\HasInDatabase;
    }
}
