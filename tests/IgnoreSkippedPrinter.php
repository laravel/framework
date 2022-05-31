<?php

namespace Illuminate\Tests;

use PHPUnit\Framework\TestResult;
use PHPUnit\Runner\Version;
use PHPUnit\TextUI\DefaultResultPrinter as PHPUnit9ResultPrinter;
use PHPUnit\TextUI\ResultPrinter as PHPUnit8ResultPrinter;

if (class_exists(Version::class) && (int) Version::series()[0] >= 9) {
    class IgnoreSkippedPrinter extends PHPUnit9ResultPrinter
    {
        protected function printSkipped(TestResult $result): void
        {
            //
        }
    }
} else {
    class IgnoreSkippedPrinter extends PHPUnit8ResultPrinter
    {
        protected function printSkipped(TestResult $result): void
        {
            //
        }
    }
}
