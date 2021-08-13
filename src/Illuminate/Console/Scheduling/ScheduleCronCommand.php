<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;

class ScheduleCronCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:cron {--remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add/Remove scheduler cron config';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $path = exec('pwd');

        if ($this->option('remove')) {
            $this->removeCron($path);

            return $this->info('The Scheduler removed!');
        }

        $this->addCron($path);

        $this->info('The Scheduler added!');
    }

    private function removeCron($path)
    {
        return $this->exec("crontab  -l | grep -v '{$path}'  | crontab -");
    }

    private function addCron($path)
    {
        $command = sprintf(
            '(crontab -l ; echo "* * * * * cd %s && php artisan schedule:run >> /dev/null 2>&1") | crontab -',
            $path
        );

        return $this->exec($command);
    }

    private function exec($command)
    {
        shell_exec("bash -c '{$command}'");
    }
}
