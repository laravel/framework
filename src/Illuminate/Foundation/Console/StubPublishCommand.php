<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Events\PublishingStubs;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'stub:publish')]
class StubPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stub:publish
                    {--existing : Publish and overwrite only the files that have already been published}
                    {--force : Overwrite any existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all stubs that are available for customization';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! is_dir($stubsPath = $this->laravel->basePath('stubs'))) {
            (new Filesystem)->makeDirectory($stubsPath);
        }

        $stubs = [
            __DIR__.'/stubs/cast.inbound.stub' => 'cast.inbound.stub',
            __DIR__.'/stubs/cast.stub' => 'cast.stub',
            __DIR__.'/stubs/console.stub' => 'console.stub',
            __DIR__.'/stubs/event.stub' => 'event.stub',
            __DIR__.'/stubs/job.queued.stub' => 'job.queued.stub',
            __DIR__.'/stubs/job.stub' => 'job.stub',
            __DIR__.'/stubs/mail.stub' => 'mail.stub',
            __DIR__.'/stubs/markdown-mail.stub' => 'markdown-mail.stub',
            __DIR__.'/stubs/markdown-notification.stub' => 'markdown-notification.stub',
            __DIR__.'/stubs/model.pivot.stub' => 'model.pivot.stub',
            __DIR__.'/stubs/model.stub' => 'model.stub',
            __DIR__.'/stubs/notification.stub' => 'notification.stub',
            __DIR__.'/stubs/observer.plain.stub' => 'observer.plain.stub',
            __DIR__.'/stubs/observer.stub' => 'observer.stub',
            __DIR__.'/stubs/policy.plain.stub' => 'policy.plain.stub',
            __DIR__.'/stubs/policy.stub' => 'policy.stub',
            __DIR__.'/stubs/provider.stub' => 'provider.stub',
            __DIR__.'/stubs/request.stub' => 'request.stub',
            __DIR__.'/stubs/resource.stub' => 'resource.stub',
            __DIR__.'/stubs/resource-collection.stub' => 'resource-collection.stub',
            __DIR__.'/stubs/rule.stub' => 'rule.stub',
            __DIR__.'/stubs/scope.stub' => 'scope.stub',
            __DIR__.'/stubs/test.stub' => 'test.stub',
            __DIR__.'/stubs/test.unit.stub' => 'test.unit.stub',
            __DIR__.'/stubs/view-component.stub' => 'view-component.stub',
            dirname(__DIR__, 2) . '/Database/Console/Factories/stubs/factory.stub' => 'factory.stub',
            dirname(__DIR__, 2) . '/Database/Console/Seeds/stubs/seeder.stub' => 'seeder.stub',
            dirname(__DIR__, 2) . '/Database/Migrations/stubs/migration.create.stub' => 'migration.create.stub',
            dirname(__DIR__, 2) . '/Database/Migrations/stubs/migration.stub' => 'migration.stub',
            dirname(__DIR__, 2) . '/Database/Migrations/stubs/migration.update.stub' => 'migration.update.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.api.stub' => 'controller.api.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.invokable.stub' => 'controller.invokable.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.model.api.stub' => 'controller.model.api.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.model.stub' => 'controller.model.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.nested.api.stub' => 'controller.nested.api.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.nested.singleton.api.stub' => 'controller.nested.singleton.api.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.nested.singleton.stub' => 'controller.nested.singleton.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.nested.stub' => 'controller.nested.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.plain.stub' => 'controller.plain.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.singleton.api.stub' => 'controller.singleton.api.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.singleton.stub' => 'controller.singleton.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/controller.stub' => 'controller.stub',
            dirname(__DIR__, 2) . '/Routing/Console/stubs/middleware.stub' => 'middleware.stub',
        ];

        $this->laravel['events']->dispatch($event = new PublishingStubs($stubs));

        foreach ($event->stubs as $from => $to) {
            $to = $stubsPath.DIRECTORY_SEPARATOR.ltrim($to, DIRECTORY_SEPARATOR);

            if ((! $this->option('existing') && (! file_exists($to) || $this->option('force')))
                || ($this->option('existing') && file_exists($to))) {
                file_put_contents($to, file_get_contents($from));
            }
        }

        $this->components->info('Stubs published successfully.');
    }
}
