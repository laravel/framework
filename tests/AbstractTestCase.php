<?php

namespace Illuminate\Tests;

use Mockery;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $now;

    protected function setUp()
    {
        parent::setUp();
        Carbon::setTestNow($this->now = Carbon::now('UTC'));
    }

    protected function tearDown()
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }
}
