<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Illuminate\Foundation\Events\PublishingStubs;
use Illuminate\Support\Facades\File;

class StubPublishedCommandTest extends TestCase
{
    public function testItCanPublishedStubFiles()
    {
        Event::fake();

        $stubs = $this->getAllStubs();

        $this->artisan('stub:publish')
            ->assertExitCode(0)
            ->expectsOutputToContain('Stubs published successfully.');

        Event::assertDispatched(PublishingStubs::class);

        $this->assertDirectoryExists(base_path('stubs'));

        $this->assertFileExists(base_path('stubs'.DIRECTORY_SEPARATOR.$stubs[array_rand($stubs)]));
    }

    public function testItCanOverWriteAnyExistingFiles()
    {
        if (! is_dir($stubsPath = base_path('stubs'))) {
            File::makeDirectory($stubsPath);
        }

        $stubs = $this->getAllStubs();

        $existingStubFile = $stubsPath.DIRECTORY_SEPARATOR.ltrim($stubs[array_rand($stubs)], DIRECTORY_SEPARATOR);

        file_put_contents($existingStubFile, $data = fake()->sentence());

        $this->artisan('stub:publish --force')
            ->assertExitCode(0)
            ->expectsOutputToContain('Stubs published successfully.');

        $this->assertNotEquals(Process::run('cat ' . $existingStubFile)->output(), $data);
    }

    private function getAllStubs()
    {
        return [
            'cast.inbound.stub',
            'cast.stub',
            'console.stub',
            'event.stub',
            'job.queued.stub',
            'job.stub',
            'mail.stub',
            'markdown-mail.stub',
            'markdown-notification.stub',
            'model.pivot.stub',
            'model.stub',
            'notification.stub',
            'observer.plain.stub',
            'observer.stub',
            'policy.plain.stub',
            'policy.stub',
            'provider.stub',
            'request.stub',
            'resource.stub',
            'resource-collection.stub',
            'rule.stub',
            'scope.stub',
            'test.stub',
            'test.unit.stub',
            'view-component.stub',
            'factory.stub',
            'seeder.stub',
            'migration.create.stub',
            'migration.stub',
            'migration.update.stub',
            'controller.api.stub',
            'controller.invokable.stub',
            'controller.model.api.stub',
            'controller.model.stub',
            'controller.nested.api.stub',
            'controller.nested.singleton.api.stub',
            'controller.nested.singleton.stub',
            'controller.nested.stub',
            'controller.plain.stub',
            'controller.singleton.api.stub',
            'controller.singleton.stub',
            'controller.stub',
            'middleware.stub',
        ];
    }
}
