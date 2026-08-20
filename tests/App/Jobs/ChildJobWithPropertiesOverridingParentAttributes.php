<?php

namespace Illuminate\Tests\App\Jobs;

class ChildJobWithPropertiesOverridingParentAttributes extends ParentJobWithAttributes
{
    public $backoff = 13;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}
