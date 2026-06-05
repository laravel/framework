<?php

namespace Tests\Unit\Console;

use Illuminate\Foundation\Console\DocsCommand;
use PHPUnit\Framework\TestCase;

class DocsCommandTest extends TestCase
{
    /**
     * Test that openViaBuiltInStrategy uses Process with an argument array
     * (not fromShellCommandline) to prevent shell injection.
     */
    public function testOpenViaBuiltInStrategyUsesProcessArrayArguments()
    {
        $ref = new \ReflectionMethod(DocsCommand::class, 'openViaBuiltInStrategy');

        $lines = array_slice(
            file($ref->getFileName()),
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        );
        $methodBody = implode('', $lines);

        // The security fix: Process must be constructed with an array of arguments.
        $this->assertStringContainsString('new Process([', $methodBody,
            'openViaBuiltInStrategy must use new Process([...]) with an argument array.');

        // Ensure the unsafe shell-based construction is not used.
        $this->assertStringNotContainsString('fromShellCommandline', $methodBody,
            'openViaBuiltInStrategy must not use Process::fromShellCommandline().');
    }

    /**
     * Test that when no binary is found the method returns early without throwing.
     */
    public function testOpenViaBuiltInStrategyReturnsWhenNoBinaryFound()
    {
        $command = new DocsCommand();

        // Inject a stub for the protected $components property.
        $componentsStub = new class
        {
            public $warned = false;

            public function warn($msg)
            {
                $this->warned = true;
            }

            public function info($msg)
            {
            }
        };
        $setComponents = function ($value) {
            $this->components = $value;
        };
        $setComponents = $setComponents->bindTo($command, $command);
        $setComponents($componentsStub);

        // Set systemOsFamily to Linux.
        $setOsFamily = function ($value) {
            $this->systemOsFamily = $value;
        };
        $setOsFamily = $setOsFamily->bindTo($command, $command);
        $setOsFamily('Linux');

        // Temporarily set PATH to an empty directory so ExecutableFinder finds nothing.
        $originalPath = getenv('PATH');
        putenv('PATH=/nonexistent_path_for_test');

        try {
            $invoke = function ($url) {
                $this->openViaBuiltInStrategy($url);
            };
            $invoke = $invoke->bindTo($command, $command);
            $invoke('https://example.com');
        } finally {
            putenv("PATH=$originalPath");
        }

        $this->assertTrue($componentsStub->warned,
            'A warning should be emitted when no suitable binary is found.');
    }
}
