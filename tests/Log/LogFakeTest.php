<?php

namespace Illuminate\Tests\Support;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Testing\Fakes\LogFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class LogFakeTest extends TestCase
{
    protected $message = 'Oh Hai Mark!';

    public function setUp()
    {
        parent::setUp();

        Container::setInstance(new Container)->singleton('config', function () {
            return new Config(['logging' => ['default' => ['stack']]]);
        });
    }

    public function testAssertLogged()
    {
        $log = new LogFake;

        try {
            $log->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged.'));
        }

        $log->info($this->message);

        $log->assertLogged('info');
    }

    public function testAssertLoggedWithNumericCallback()
    {
        $log = new LogFake;

        try {
            $log->assertLogged('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times.'));
        }

        $log->info($this->message);

        $log->assertLogged('info', 1);
    }

    public function testAssertLoggedWithMessageCheckingCallback()
    {
        $log = new LogFake;

        try {
            $log->assertLogged('info', function ($message) {
                return $message === $this->message;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged.'));
        }

        $log->info($this->message);

        $log->assertLogged('info', function ($message) {
            return $message === $this->message;
        });
    }

    public function testAssertLoggedTimes()
    {
        $log = new LogFake;

        try {
            $log->assertLoggedTimes('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times.'));
        }

        $log->info($this->message);

        $log->assertLoggedTimes('info', 1);
    }

    public function testAssertNotLogged()
    {
        $log = new LogFake;

        $log->assertNotLogged('info');

        try {
            $log->info($this->message);
            $log->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged.'));
        }
    }

    public function testAssertNotLoggedWithCallback()
    {
        $log = new LogFake;

        $log->assertNotLogged('info', function ($message) {
            return $message === $this->message;
        });

        try {
            $log->info($this->message);
            $log->assertNotLogged('info', function ($message) {
                return $message === $this->message;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged.'));
        }
    }

    public function testAssertNothingLogged()
    {
        $log = new LogFake;

        $log->assertNothingLogged();

        try {
            $log->info($this->message);
            $log->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Logs were created.'));
        }
    }

    public function testLogged()
    {
        $log = new LogFake;

        $this->assertTrue($log->logged('info')->isEmpty());

        $log->info($this->message);
        $this->assertFalse($log->logged('info')->isEmpty());
    }

    public function testLoggedWithCallback()
    {
        $log = new LogFake;

        $this->assertTrue($log->logged('info', function ($message) {
            return $this->message === $message;
        })->isEmpty());

        $log->info($this->message);

        $this->assertFalse($log->logged('info', function ($message) {
            return $this->message === $message;
        })->isEmpty());
    }

    public function testHasLogged()
    {
        $log = new LogFake;

        $this->assertFalse($log->hasLogged('info'));

        $log->info($this->message);

        $this->assertTrue($log->hasLogged('info'));
    }

    public function testHasNotLogged()
    {
        $log = new LogFake;

        $this->assertTrue($log->hasNotLogged('info'));

        $log->info($this->message);

        $this->assertFalse($log->hasNotLogged('info'));
    }

    public function testCurrentChannelIsTakenIntoAccount()
    {
        $log = new LogFake;

        $log->channel('slack')->info($this->message);

        $log->assertNotLogged('info');
        $log->channel('slack')->assertLogged('info');
    }

    public function testCurrentStackIsTakenIntoAccount()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info($this->message);

        $log->assertNotLogged('info');
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged('info');
    }

    public function testCanHaveStackChannelsInAnyOrder()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info($this->message);

        $log->assertNotLogged('info');
        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged('info');
    }

    public function testDifferentiatesBetweenStacksWithANameAndThoseWithout()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info($this->message);
        $log->stack(['bugsnag', 'sentry'])->alert($this->message);

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged('alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged('info');
    }

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'])->info($this->message);
        $log->channel('bugsnag.sentry')->alert($this->message);

        $log->stack(['bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('bugsnag.sentry')->assertNotLogged('info');

        $log->stack(['bugsnag', 'sentry'], 'name')->info($this->message);
        $log->channel('name.bugsnag.sentry')->alert($this->message);

        $log->stack(['name', 'bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('name.bugsnag.sentry')->assertNotLogged('info');
    }
}
