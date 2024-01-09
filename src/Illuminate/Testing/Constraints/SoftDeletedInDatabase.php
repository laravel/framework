<?php

namespace Illuminate\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Runner\Version;

if (str_starts_with(Version::series(), '10')) {
    class SoftDeletedInDatabase extends Constraint
    {
        use Concerns\SoftDeletedInDatabase;
    }
} else {
    readonly class SoftDeletedInDatabase extends Constraint
    {
        use Concerns\SoftDeletedInDatabase;
    }
}
