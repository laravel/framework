<?php

namespace Illuminate\Foundation\Testing\Attributes;

use Attribute;

/**
 * Run a test without configuring the Laravel framework.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class UnitTest
{
}
