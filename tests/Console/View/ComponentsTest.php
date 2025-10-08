<?php

namespace Illuminate\Tests\Console\View;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components;
use Illuminate\Database\Migrations\MigrationResult;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set a sufficient terminal width for tests to prevent text truncation
        putenv('COLUMNS=120');
    }

    protected function tearDown(): void
    {
        m::close();

        // Clean up environment variable
        putenv('COLUMNS');
    }

    /**
     * Create an OutputStyle instance with a BufferedOutput for testing.
     *
     * @return array{OutputStyle, BufferedOutput}
     */
    private function createOutputStyle(): array
    {
        $bufferedOutput = new BufferedOutput();
        $input = new ArrayInput([]);
        $outputStyle = new OutputStyle($input, $bufferedOutput);

        return [$outputStyle, $bufferedOutput];
    }

    public function testAlert()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\Alert($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString(
            'THE APPLICATION IS IN THE [PRODUCTION] ENVIRONMENT.',
            $bufferedOutput->fetch()
        );
    }

    public function testBulletList()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\BulletList($output))->render([
            'ls -la',
            'php artisan inspire',
        ]);

        $result = $bufferedOutput->fetch();

        $this->assertStringContainsString('⇂ ls -la', $result);
        $this->assertStringContainsString('⇂ php artisan inspire', $result);
    }

    public function testSuccess()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\Success($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('SUCCESS  The application is in the [production] environment.', $bufferedOutput->fetch());
    }

    public function testError()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\Error($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('ERROR  The application is in the [production] environment.', $bufferedOutput->fetch());
    }

    public function testInfo()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\Info($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('INFO  The application is in the [production] environment.', $bufferedOutput->fetch());
    }

    public function testConfirm()
    {
        $output = m::mock(OutputStyle::class);

        $output->shouldReceive('confirm')
            ->with('Question?', false)
            ->once()
            ->andReturnTrue();

        $result = (new Components\Confirm($output))->render('Question?');
        $this->assertTrue($result);

        $output->shouldReceive('confirm')
            ->with('Question?', true)
            ->once()
            ->andReturnTrue();

        $result = (new Components\Confirm($output))->render('Question?', true);
        $this->assertTrue($result);
    }

    public function testChoice()
    {
        $output = m::mock(OutputStyle::class);

        $output->shouldReceive('askQuestion')
            ->with(m::type(ChoiceQuestion::class))
            ->once()
            ->andReturn('a');

        $result = (new Components\Choice($output))->render('Question?', ['a', 'b']);
        $this->assertSame('a', $result);
    }

    public function testTask()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\Task($output))->render('My task', fn () => MigrationResult::Success->value);
        $result = $bufferedOutput->fetch();
        $this->assertStringContainsString('My task', $result);
        $this->assertStringContainsString('DONE', $result);

        (new Components\Task($output))->render('My task', fn () => MigrationResult::Failure->value);
        $result = $bufferedOutput->fetch();
        $this->assertStringContainsString('My task', $result);
        $this->assertStringContainsString('FAIL', $result);

        (new Components\Task($output))->render('My task', fn () => MigrationResult::Skipped->value);
        $result = $bufferedOutput->fetch();
        $this->assertStringContainsString('My task', $result);
        $this->assertStringContainsString('SKIPPED', $result);
    }

    public function testTwoColumnDetail()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\TwoColumnDetail($output))->render('First', 'Second');
        $result = $bufferedOutput->fetch();
        $this->assertStringContainsString('First', $result);
        $this->assertStringContainsString('Second', $result);
    }

    public function testWarn()
    {
        [$output, $bufferedOutput] = $this->createOutputStyle();

        (new Components\Warn($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('WARN  The application is in the [production] environment.', $bufferedOutput->fetch());
    }
}
