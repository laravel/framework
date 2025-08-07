<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ViewClearCommand;
use Illuminate\Queue\Console\FlushFailedCommand;
use Orchestra\Testbench\TestCase;

class ConfirmationSafetyTest extends TestCase
{
    public function test_commands_use_confirmable_trait()
    {
        // Verify that our destructive commands now use the ConfirmableTrait
        $flushCommand = new FlushFailedCommand();
        $this->assertTrue(method_exists($flushCommand, 'confirmToProceed'), 'FlushFailedCommand should have confirmToProceed method');

        $cacheCommand = new ClearCommand(
            $this->app->make('cache'),
            $this->app->make('files')
        );
        $this->assertTrue(method_exists($cacheCommand, 'confirmToProceed'), 'ClearCommand should have confirmToProceed method');

        $configCommand = new ConfigClearCommand($this->app->make('files'));
        $this->assertTrue(method_exists($configCommand, 'confirmToProceed'), 'ConfigClearCommand should have confirmToProceed method');

        $viewCommand = new ViewClearCommand($this->app->make('files'));
        $this->assertTrue(method_exists($viewCommand, 'confirmToProceed'), 'ViewClearCommand should have confirmToProceed method');
    }

    public function test_commands_have_force_option()
    {
        // Verify that our commands now have the --force option for production
        $flushCommand = new FlushFailedCommand();
        $flushCommand->setLaravel($this->app);

        $definition = $flushCommand->getDefinition();
        $this->assertTrue($definition->hasOption('force'), 'FlushFailedCommand should have --force option');

        $cacheCommand = new ClearCommand(
            $this->app->make('cache'),
            $this->app->make('files')
        );
        $cacheCommand->setLaravel($this->app);

        $definition = $cacheCommand->getDefinition();
        $this->assertTrue($definition->hasOption('force'), 'ClearCommand should have --force option');

        $configCommand = new ConfigClearCommand($this->app->make('files'));
        $configCommand->setLaravel($this->app);

        $definition = $configCommand->getDefinition();
        $this->assertTrue($definition->hasOption('force'), 'ConfigClearCommand should have --force option');

        $viewCommand = new ViewClearCommand($this->app->make('files'));
        $viewCommand->setLaravel($this->app);

        $definition = $viewCommand->getDefinition();
        $this->assertTrue($definition->hasOption('force'), 'ViewClearCommand should have --force option');
    }
}
