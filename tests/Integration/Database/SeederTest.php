<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Stringable;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class SeederTest extends TestCase
{
    /**
     * @var \Mockery\Mock
     */
    private $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = m::mock(OutputInterface::class);

        TestRunningSeeder::$wasRun = false;
        TestSkippedSeeder::$wasRun = false;
    }

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testSeedWithShouldRunReturningFalse()
    {
        $seeder = new TestDatabaseSeeder;
        $seeder->setCommand($this->createMockCommand());

        $this->expectTwoColumnDetail('Illuminate\Tests\Integration\Database\TestRunningSeeder');
        $this->expectTwoColumnDetail('Illuminate\Tests\Integration\Database\TestSkippedSeeder', 'SKIPPED');
        $this->output->shouldReceive('writeln')->times(2);

        $seeder->call([
            TestRunningSeeder::class,
            TestSkippedSeeder::class,
        ]);

        $this->assertTrue(TestRunningSeeder::$wasRun);
        $this->assertFalse(TestSkippedSeeder::$wasRun);
    }

    public function testSeedWithShouldRunReturningTrue()
    {
        $seeder = new TestDatabaseSeeder;
        $seeder->setCommand($this->createMockCommand());

        $this->expectTwoColumnDetail('Illuminate\Tests\Integration\Database\TestRunningSeeder');
        $this->output->shouldReceive('writeln')->once();

        $seeder->call(TestRunningSeeder::class);

        $this->assertTrue(TestRunningSeeder::$wasRun);
    }

    public function testSilentSeedWithShouldRunReturningFalse()
    {
        $seeder = new TestDatabaseSeeder;

        $seeder->callSilent(TestSkippedSeeder::class);

        $this->assertFalse(TestSkippedSeeder::$wasRun);
    }

    protected function createMockCommand()
    {
        $command = m::mock(\Illuminate\Console\Command::class);
        $command->shouldReceive('getOutput')->andReturn($this->output);

        return $command;
    }

    protected function expectTwoColumnDetail($first, $second = null)
    {
        $this->output->shouldReceive('writeln')->with(m::on(function ($argument) use ($first, $second) {
            $result = (new Stringable($argument))->contains($first);

            if ($result && $second) {
                $result = (new Stringable($argument))->contains($second);
            }

            return $result;
        }), m::any());
    }
}

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}

class TestRunningSeeder extends Seeder
{
    public static $wasRun = false;

    public function shouldRun(): bool
    {
        return true;
    }

    public function run(): void
    {
        self::$wasRun = true;
    }
}

class TestSkippedSeeder extends Seeder
{
    public static $wasRun = false;

    public function shouldRun(): bool
    {
        return false;
    }

    public function run(): void
    {
        self::$wasRun = true;
    }
}
