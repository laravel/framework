<?php

namespace Illuminate\Tests;

use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\DefaultResultPrinter;

class IgnoreSkippedPrinter extends DefaultResultPrinter
{
    protected function printSkipped(TestResult $result): void
    {
        //
    }
}
