<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class StubPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stub:publish';

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
        $files = [
            __DIR__.'/stubs/job.queued.stub' => base_path('stubs/job.queued.stub'),
            __DIR__.'/stubs/job.stub' => base_path('stubs/job.stub'),
            __DIR__.'/stubs/job.stub' => base_path('stubs/job.stub'),
            __DIR__.'/stubs/model.pivot.stub' => base_path('stubs/model.pivot.stub'),
            __DIR__.'/stubs/model.stub' => base_path('stubs/model.stub'),
            __DIR__.'/stubs/test.stub' => base_path('stubs/test.stub'),
            __DIR__.'/stubs/test.unit.stub' => base_path('stubs/test.unit.stub'),
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.create.stub') => base_path('stubs/migration.create.stub'),
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.stub') => base_path('stubs/migration.stub'),
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.update.stub') => base_path('stubs/migration.update.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.api.stub') => base_path('stubs/controller.api.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.invokable.stub') => base_path('stubs/controller.invokable.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.model.api.stub') => base_path('stubs/controller.model.api.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.model.stub') => base_path('stubs/controller.model.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.api.stub') => base_path('stubs/controller.nested.api.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.stub') => base_path('stubs/controller.nested.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.plain.stub') => base_path('stubs/controller.plain.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.stub') => base_path('stubs/controller.stub'),
        ];

        if (! is_dir(base_path('stubs'))) {
            (new Filesystem)->makeDirectory(base_path('stubs'));
        }

        foreach ($files as $from => $to) {
            file_put_contents($to, file_get_contents($from));
        }

        $this->info('Stubs published successfully.');
    }
}
