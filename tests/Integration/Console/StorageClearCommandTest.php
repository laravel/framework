<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class StorageClearCommandTest extends TestCase
{
    public function testClearAllFilesFromDisk()
    {
        Storage::fake('local');

        Storage::disk('local')->put('file1.txt', 'test');
        Storage::disk('local')->put('file2.txt', 'test');

        $this->artisan('storage:clear')
        ->assertExitCode(0)
        ->expectsOutput('Cleared all contents on disk [local].');

        Storage::disk('local')->assertMissing('file1.txt');
        Storage::disk('local')->assertMissing('file2.txt');
    }

    public function testClearSpecificFolderFromDisk()
    {
        Storage::fake('local');

        Storage::disk('local')->put('folder1/file1.txt', 'test');
        Storage::disk('local')->put('folder1/subfolder/file2.txt', 'test');
        Storage::disk('local')->put('folder2/keep.txt', 'test');

        $this->artisan('storage:clear', [
            '--disk' => 'local',
            '--folder' => 'folder1',
        ])
        ->assertExitCode(0)
        ->expectsOutput('Cleared folder [folder1] on disk [local].');

        Storage::disk('local')->assertMissing('folder1/file1.txt');
        Storage::disk('local')->assertMissing('folder1/subfolder/file2.txt');
        Storage::disk('local')->assertExists('folder2/keep.txt');
    }

    public function testThrowsErrorForInvalidDisk()
    {
        $invalidDisk = 'invalidDisk';
        $availableDisks = implode(', ', array_keys(config('filesystems.disks', [])));

        $this->artisan('storage:clear', [
            '--disk' => $invalidDisk,
        ])
        ->assertExitCode(0)
        ->expectsOutput("Disk [{$invalidDisk}] is not configured. Available disks: {$availableDisks}");
    }

    public function testSkipsIfUserDoesNotConfirm()
    {
        $this->app['env'] = 'production';
        Storage::fake('local');
        Storage::disk('local')->put('file.txt', 'test');

        $this->artisan('storage:clear', [
            '--disk' => 'local',
        ])
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertExitCode(0);

        Storage::disk('local')->assertExists('file.txt');
    }

    public function testForceOptionBypassesConfirmationInProduction()
    {
        $this->app['env'] = 'production';

        Storage::fake('local');
        Storage::disk('local')->put('file.txt', 'test');

        $this->artisan('storage:clear', [
            '--disk' => 'local',
            '--force' => true,
        ])
        ->assertExitCode(0)
        ->expectsOutput('Cleared all contents on disk [local].');

        Storage::disk('local')->assertMissing('file.txt');
    }

    public function testGitignoreIsSkippedWhenClearingLocalDisk()
    {
        Storage::fake('local');
        Storage::disk('local')->put('.gitignore', 'content');
        Storage::disk('local')->put('delete-me.txt', 'content');

        $this->artisan('storage:clear', [
            '--disk' => 'local',
        ])
        ->assertExitCode(0)
        ->expectsOutput('Skipping [.gitignore] on [local] disk.')
        ->expectsOutput('Cleared all contents on disk [local].');

        Storage::disk('local')->assertExists('.gitignore');
        Storage::disk('local')->assertMissing('delete-me.txt');
    }

    public function testFolderNotFoundShowsError()
    {
        Storage::fake('local');

        $this->artisan('storage:clear', [
            '--disk' => 'local',
            '--folder' => 'nonexistent',
        ])
        ->assertExitCode(0)
        ->expectsOutput('The folder [nonexistent] does not exist on the [local] disk.');
    }
}
