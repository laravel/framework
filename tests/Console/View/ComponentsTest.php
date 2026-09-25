<?php

namespace Illuminate\Tests\Console\View;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components;
use Illuminate\Database\Migrations\MigrationResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ComponentsTest extends TestCase
{
    public function testAlert()
    {
        $output = new BufferedOutput();

        (new Components\Alert($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString(
            'THE APPLICATION IS IN THE [PRODUCTION] ENVIRONMENT.',
            $output->fetch()
        );
    }

    public function testBulletList()
    {
        $output = new BufferedOutput();

        (new Components\BulletList($output))->render([
            'ls -la',
            'php artisan inspire',
        ]);

        $output = $output->fetch();

        $this->assertStringContainsString('⇂ ls -la', $output);
        $this->assertStringContainsString('⇂ php artisan inspire', $output);
    }

    public function testSuccess()
    {
        $output = new BufferedOutput();

        (new Components\Success($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('SUCCESS  The application is in the [production] environment.', $output->fetch());
    }

    public function testError()
    {
        $output = new BufferedOutput();

        (new Components\Error($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('ERROR  The application is in the [production] environment.', $output->fetch());
    }

    public function testInfo()
    {
        $output = new BufferedOutput();

        (new Components\Info($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('INFO  The application is in the [production] environment.', $output->fetch());
    }

    public function testConfirm()
    {
        $result = (new Components\Confirm($this->outputStyleWithAnswer('')))->render('Question?');
        $this->assertFalse($result);

        $result = (new Components\Confirm($this->outputStyleWithAnswer('')))->render('Question?', true);
        $this->assertTrue($result);
    }

    public function testChoice()
    {
        $result = (new Components\Choice($this->outputStyleWithAnswer('a')))->render('Question?', ['a', 'b']);
        $this->assertSame('a', $result);
    }

    /**
     * Build a real OutputStyle whose interactive input is pre-fed the given typed answer.
     */
    protected function outputStyleWithAnswer($answer)
    {
        $input = new ArrayInput([]);

        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $answer."\n");
        rewind($stream);
        $input->setStream($stream);

        return new OutputStyle($input, new BufferedOutput);
    }

    public function testTask()
    {
        $output = new BufferedOutput();

        (new Components\Task($output))->render('My task', fn () => MigrationResult::Success->value);
        $result = $output->fetch();
        $this->assertStringContainsString('My task', $result);
        $this->assertStringContainsString('DONE', $result);

        (new Components\Task($output))->render('My task', fn () => MigrationResult::Failure->value);
        $result = $output->fetch();
        $this->assertStringContainsString('My task', $result);
        $this->assertStringContainsString('FAIL', $result);

        (new Components\Task($output))->render('My task', fn () => MigrationResult::Skipped->value);
        $result = $output->fetch();
        $this->assertStringContainsString('My task', $result);
        $this->assertStringContainsString('SKIPPED', $result);
    }

    public function testTwoColumnDetail()
    {
        $output = new BufferedOutput();

        (new Components\TwoColumnDetail($output))->render('First', 'Second');
        $result = $output->fetch();
        $this->assertStringContainsString('First', $result);
        $this->assertStringContainsString('Second', $result);
    }

    public function testTwoColumnDetailPreservesTrailingPunctuationInValue()
    {
        $output = new BufferedOutput();

        (new Components\TwoColumnDetail($output))->render('Key', 'value!');
        $result = $output->fetch();
        $this->assertStringContainsString('value!', $result);
    }

    public function testWarn()
    {
        $output = new BufferedOutput();

        (new Components\Warn($output))->render('The application is in the [production] environment');

        $this->assertStringContainsString('WARN  The application is in the [production] environment.', $output->fetch());
    }
}
