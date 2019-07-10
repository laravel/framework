<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class StorageLinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from "%s/storage" to "%s/app/public"';

    /**
     * Set the Laravel application instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $laravel
     * @return void
     */
    public function setLaravel($laravel)
    {
        parent::setLaravel($laravel);

        $filesystem = $laravel->make('files');
        $this->setDescription(sprintf(
            $this->description,
            $filesystem->basename(public_path()),
            $filesystem->basename(storage_path())
        ));
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $filesystem = $this->laravel->make('files');

        if ($filesystem->exists(public_path('storage'))) {
            return $this->error(sprintf(
                'The "%s/storage" directory already exists.',
                $filesystem->basename(public_path())
            ));
        }

        $filesystem->link(
            storage_path('app/public'),
            public_path('storage')
        );

        $this->info(sprintf(
            'The [%s/storage] directory has been linked.',
            $filesystem->basename(storage_path())
        ));
    }
}
