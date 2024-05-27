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
                    {--force : Overwrite any existing files}
                    {--only=* : Stub categories to publish (e.g. migration,controller)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish stubs that are available for customization';

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

        $stubCategories = [
            'cast' => [
                __DIR__.'/stubs/cast.inbound.stub' => 'cast.inbound.stub',
                __DIR__.'/stubs/cast.stub' => 'cast.stub',
            ],
            'class' => [
                __DIR__.'/stubs/class.stub' => 'class.stub',
                __DIR__.'/stubs/class.invokable.stub' => 'class.invokable.stub',
            ],
            'console' => [
                __DIR__.'/stubs/console.stub' => 'console.stub',
            ],
            'enum' => [
                __DIR__.'/stubs/enum.stub' => 'enum.stub',
                __DIR__.'/stubs/enum.backed.stub' => 'enum.backed.stub',
            ],
            'event' => [
                __DIR__.'/stubs/event.stub' => 'event.stub',
            ],
            'job' => [
                __DIR__.'/stubs/job.queued.stub' => 'job.queued.stub',
                __DIR__.'/stubs/job.stub' => 'job.stub',
            ],
            'listener' => [
                __DIR__.'/stubs/listener.typed.queued.stub' => 'listener.typed.queued.stub',
                __DIR__.'/stubs/listener.queued.stub' => 'listener.queued.stub',
                __DIR__.'/stubs/listener.typed.stub' => 'listener.typed.stub',
                __DIR__.'/stubs/listener.stub' => 'listener.stub',
            ],
            'mail' => [
                __DIR__.'/stubs/mail.stub' => 'mail.stub',
                __DIR__.'/stubs/markdown-mail.stub' => 'markdown-mail.stub',
            ],
            'notification' => [
                __DIR__.'/stubs/notification.stub' => 'notification.stub',
                __DIR__.'/stubs/markdown-notification.stub' => 'markdown-notification.stub',
            ],
            'model' => [
                __DIR__.'/stubs/model.pivot.stub' => 'model.pivot.stub',
                __DIR__.'/stubs/model.stub' => 'model.stub',
            ],
            'observer' => [
                __DIR__.'/stubs/observer.plain.stub' => 'observer.plain.stub',
                __DIR__.'/stubs/observer.stub' => 'observer.stub',
            ],
            'policy' => [
                __DIR__.'/stubs/policy.plain.stub' => 'policy.plain.stub',
                __DIR__.'/stubs/policy.stub' => 'policy.stub',
            ],
            'provider' => [
                __DIR__.'/stubs/provider.stub' => 'provider.stub',
            ],
            'request' => [
                __DIR__.'/stubs/request.stub' => 'request.stub',
            ],
            'resource' => [
                __DIR__.'/stubs/resource.stub' => 'resource.stub',
                __DIR__.'/stubs/resource-collection.stub' => 'resource-collection.stub',
            ],
            'rule' => [
                __DIR__.'/stubs/rule.stub' => 'rule.stub',
            ],
            'scope' => [
                __DIR__.'/stubs/scope.stub' => 'scope.stub',
            ],
            'test' => [
                __DIR__.'/stubs/test.stub' => 'test.stub',
                __DIR__.'/stubs/test.unit.stub' => 'test.unit.stub',
            ],
            'trait' => [
                __DIR__.'/stubs/trait.stub' => 'trait.stub',
            ],
            'view-component' => [
                __DIR__.'/stubs/view-component.stub' => 'view-component.stub',
            ],
            'factory' => [
                realpath(__DIR__.'/../../Database/Console/Factories/stubs/factory.stub') => 'factory.stub',
            ],
            'seeder' => [
                realpath(__DIR__.'/../../Database/Console/Seeds/stubs/seeder.stub') => 'seeder.stub',
            ],
            'migration' => [
                realpath(__DIR__.'/../../Database/Migrations/stubs/migration.create.stub') => 'migration.create.stub',
                realpath(__DIR__.'/../../Database/Migrations/stubs/migration.stub') => 'migration.stub',
                realpath(__DIR__.'/../../Database/Migrations/stubs/migration.update.stub') => 'migration.update.stub',
            ],
            'controller' => [
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.api.stub') => 'controller.api.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.invokable.stub') => 'controller.invokable.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.model.api.stub') => 'controller.model.api.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.model.stub') => 'controller.model.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.api.stub') => 'controller.nested.api.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.singleton.api.stub') => 'controller.nested.singleton.api.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.singleton.stub') => 'controller.nested.singleton.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.stub') => 'controller.nested.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.plain.stub') => 'controller.plain.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.singleton.api.stub') => 'controller.singleton.api.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.singleton.stub') => 'controller.singleton.stub',
                realpath(__DIR__.'/../../Routing/Console/stubs/controller.stub') => 'controller.stub',
            ],
            'middleware' => [
                realpath(__DIR__.'/../../Routing/Console/stubs/middleware.stub') => 'middleware.stub',
            ],
        ];

        $only = $this->option('only') ? explode(',', $this->option('only')) : null;

        $stubs = collect($stubCategories);
        if (! is_null($only)) {
            $stubs = $stubs->filter(function ($value, $key) use ($only) {
                return in_array($key, $only);
            });
        }
        $stubs = $stubs->flatten(1)->toArray();

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
