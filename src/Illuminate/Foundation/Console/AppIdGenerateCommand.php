<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Encryption\Encrypter;

class AppIdGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-id:generate
                    {--show : Display the app-id instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'app-id:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application app-id';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $appId = $this->generateRandomAppId();

        if ($this->option('show')) {
            return $this->line('<comment>'.$appId.'</comment>');
        }

        // Next, we will replace the application app-id in the environment file so it is
        // automatically setup for this developer. This app-id gets generated using a
        // random string for storage.
        if (! $this->setAppIdInEnvironmentFile($appId)) {
            return;
        }

        $this->laravel['config']['app.app_id'] = $appId;

        $this->info('Application app-id set successfully.');
    }

    /**
     * Generate a random app-id for the application.
     *
     * @return string
     */
    protected function generateRandomAppId()
    {
        return uniqid();
    }

    /**
     * Set the application app-id in the environment file.
     *
     * @param  string  $appId
     * @return bool
     */
    protected function setAppIdInEnvironmentFile($appId)
    {
        $currentAppId = $this->laravel['config']['app.app_id'];

        if (strlen($currentAppId) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($appId);

        return true;
    }

    /**
     * Write a new environment file with the given app-id.
     *
     * @param  string  $appId
     * @return void
     */
    protected function writeNewEnvironmentFileWith($appId)
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->appIdReplacementPattern(),
            'APP_ID='.$appId,
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Get a regex pattern that will match env APP_ID with any random app-id.
     *
     * @return string
     */
    protected function appIdReplacementPattern()
    {
        $escaped = preg_quote('='.$this->laravel['config']['app.app_id'], '/');

        return "/^APP_ID{$escaped}/m";
    }
}
