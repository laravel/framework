<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Container\Container;
use Illuminate\Testing\ParallelTesting;
use PHPUnit\Framework\TestCase;

class ParallelTestingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container);

        $_SERVER['LARAVEL_PARALLEL_TESTING'] = 1;
    }

    /**
     * @dataProvider callbacks
     */
    public function testCallbacks($callback)
    {
        $parallelTesting = new ParallelTesting(Container::getInstance());
        $caller = 'call'.ucfirst($callback).'Callbacks';

        $state = false;
        $parallelTesting->{$caller}($this);
        $this->assertFalse($state);

        $parallelTesting->{$callback}(function ($token, $testCase = null) use ($callback, &$state) {
            if (in_array($callback, ['setUpTestCase', 'tearDownTestCase'])) {
                $this->assertSame($this, $testCase);
            } else {
                $this->assertNull($testCase);
            }

            $this->assertSame('1', (string) $token);
            $state = true;
        });

        $parallelTesting->{$caller}($this);
        $this->assertFalse($state);

        $parallelTesting->resolveTokenUsing(function () {
            return '1';
        });

        $parallelTesting->{$caller}($this);
        $this->assertTrue($state);
    }

    public function testOptions()
    {
        $parallelTesting = new ParallelTesting(Container::getInstance());

        $this->assertFalse($parallelTesting->option('recreate_databases'));
        $this->assertFalse($parallelTesting->option('without_databases'));

        $parallelTesting->resolveOptionsUsing(function ($option) {
            return $option === 'recreate_databases';
        });

        $this->assertFalse($parallelTesting->option('recreate_caches'));
        $this->assertFalse($parallelTesting->option('without_databases'));
        $this->assertTrue($parallelTesting->option('recreate_databases'));

        $parallelTesting->resolveOptionsUsing(function ($option) {
            return $option === 'without_databases';
        });

        $this->assertTrue($parallelTesting->option('without_databases'));
    }

    public function testToken()
    {
        $parallelTesting = new ParallelTesting(Container::getInstance());

        $this->assertFalse($parallelTesting->token());

        $parallelTesting->resolveTokenUsing(function () {
            return '1';
        });

        $this->assertSame('1', (string) $parallelTesting->token());
    }

    public static function callbacks()
    {
        return [
            ['setUpProcess'],
            ['setUpTestCase'],
            ['setUpTestDatabase'],
            ['tearDownTestCase'],
            ['tearDownProcess'],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);

        unset($_SERVER['LARAVEL_PARALLEL_TESTING']);
    }
}
