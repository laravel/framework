<?php

namespace Illuminate\Tests\Integration\Generators;

class JobMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Jobs/FooCreated.php',
        'tests/Feature/Jobs/FooCreatedTest.php',
    ];

    public function testItCanGenerateJobFile()
    {
        $this->artisan('make:job', ['name' => 'FooCreated'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Jobs;',
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Foundation\Queue\Queueable;',
            'class FooCreated implements ShouldQueue',
            'use Queueable;',
        ], 'app/Jobs/FooCreated.php');

        $this->assertFilenameNotExists('tests/Feature/Jobs/FooCreatedTest.php');
    }

    public function testItCanGenerateSyncJobFile()
    {
        $this->artisan('make:job', ['name' => 'FooCreated', '--sync' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Jobs;',
            'use Illuminate\Foundation\Bus\Dispatchable;',
            'class FooCreated',
            'use Dispatchable;',
        ], 'app/Jobs/FooCreated.php');

        $this->assertFileNotContains([
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Foundation\Queue\Queueable;',
            'use Illuminate\Queue\InteractsWithQueue;',
            'use Illuminate\Queue\SerializesModels;',
        ], 'app/Jobs/FooCreated.php');
    }

    public function testItCanGenerateJobFileWithTest()
    {
        $this->artisan('make:job', ['name' => 'FooCreated', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Jobs/FooCreated.php');
        $this->assertFilenameExists('tests/Feature/Jobs/FooCreatedTest.php');
    }
}
