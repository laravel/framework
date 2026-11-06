<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Eloquent\ModelInspector;
use Illuminate\Foundation\Application;
use Illuminate\Tests\Database\Fixtures\Console\BufferedConsoleOutput;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ShowModelCommandTest extends TestCase
{
    protected function setUp(): void
    {
        Application::setInstance($app = new Application(__DIR__));

        $inspector = m::mock(ModelInspector::class);
        $inspector->shouldReceive('inspect')
            ->andThrow(new BindingResolutionException('Target class [App\Models\Missing] does not exist.'));

        $app->instance(ModelInspector::class, $inspector);
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);

        m::close();
    }

    public function testJsonErrorIsWrittenToStderr()
    {
        $output = new BufferedConsoleOutput;

        $status = $this->runCommand(['model' => 'Missing', '--json' => true], $output);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame('', $output->fetch());
        $this->assertSame('Target class [App\Models\Missing] does not exist.'.PHP_EOL, $output->fetchErrors());
    }

    public function testJsonErrorIsPlainTextOnStderr()
    {
        $output = new BufferedConsoleOutput;

        $this->runCommand(['model' => 'Missing', '--json' => true], $output);

        $errors = $output->fetchErrors();

        $this->assertStringNotContainsString('ERROR', $errors);
        $this->assertNull(json_decode($errors, true));
    }

    public function testErrorIsWrittenToStdoutWithoutJsonOption()
    {
        $output = new BufferedConsoleOutput;

        $status = $this->runCommand(['model' => 'Missing'], $output);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame('', $output->fetchErrors());

        $stdout = $output->fetch();

        $this->assertStringContainsString('ERROR', $stdout);
        $this->assertStringContainsString('Target class [App\Models\Missing] does not exist.', $stdout);
    }

    public function testJsonErrorFallsBackToStdoutWhenOutputHasNoErrorStream()
    {
        $output = new BufferedOutput;

        $status = $this->runCommand(['model' => 'Missing', '--json' => true], $output);

        $this->assertSame(Command::FAILURE, $status);

        $stdout = $output->fetch();

        $this->assertStringContainsString('ERROR', $stdout);
        $this->assertStringContainsString('Target class [App\Models\Missing] does not exist.', $stdout);
    }

    protected function runCommand(array $arguments, OutputInterface $output): int
    {
        $command = new ShowModelCommand;
        $command->setLaravel(Application::getInstance());

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }
}
