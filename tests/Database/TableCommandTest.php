<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\TableCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Application;
use Illuminate\Tests\Database\Fixtures\Console\BufferedConsoleOutput;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TableCommandTest extends TestCase
{
    protected SQLiteConnection $connection;

    protected function setUp(): void
    {
        Application::setInstance($app = new Application(__DIR__));

        $this->connection = new SQLiteConnection(new PDO('sqlite::memory:'));

        $resolver = new ConnectionResolver(['default' => $this->connection]);
        $resolver->setDefaultConnection('default');

        $app->instance(ConnectionResolverInterface::class, $resolver);

        $this->connection->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);
    }

    public function testJsonMissingTableIsReportedOnStderr()
    {
        $output = new BufferedConsoleOutput;

        $status = $this->runCommand(['table' => 'missing', '--json' => true], $output);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame('', $output->fetch());
        $this->assertSame("Table [missing] doesn't exist.".PHP_EOL, $output->fetchErrors());
    }

    public function testMissingTableIsReportedOnStdoutWithoutJsonOption()
    {
        $output = new BufferedConsoleOutput;

        $status = $this->runCommand(['table' => 'missing'], $output);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame('', $output->fetchErrors());

        $stdout = $output->fetch();

        $this->assertStringContainsString('WARN', $stdout);
        $this->assertStringContainsString("Table [missing] doesn't exist.", $stdout);
    }

    public function testJsonMissingTableFallsBackToStdoutWhenOutputHasNoErrorStream()
    {
        $output = new BufferedOutput;

        $status = $this->runCommand(['table' => 'missing', '--json' => true], $output);

        $this->assertSame(Command::FAILURE, $status);

        $stdout = $output->fetch();

        $this->assertStringContainsString('WARN', $stdout);
        $this->assertStringContainsString("Table [missing] doesn't exist.", $stdout);
    }

    public function testJsonExistingTableWritesOnlyJsonToStdout()
    {
        $output = new BufferedConsoleOutput;

        $status = $this->runCommand(['table' => 'users', '--json' => true], $output);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame('', $output->fetchErrors());

        $data = json_decode($output->fetch(), true);

        $this->assertIsArray($data);
        $this->assertSame('users', $data['table']['name']);
        $this->assertSame(2, $data['table']['columns']);
    }

    protected function runCommand(array $arguments, OutputInterface $output): int
    {
        $command = new TableCommand;
        $command->setLaravel(Application::getInstance());

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }
}
