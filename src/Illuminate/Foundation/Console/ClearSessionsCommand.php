<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

class ClearSessionsCommand extends Command
{
    protected $signature = 'sessions:clear';
    protected $description = 'Clear all user sessions based on the session driver';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $sessionDriver = config('session.driver'); // Get the session driver from config

        switch ($sessionDriver) {
            case 'file':
                $this->clearFileSessions();
                break;

            case 'database':
                $this->clearDatabaseSessions();
                break;

            case 'redis':
                $this->clearRedisSessions();
                break;

            default:
                $this->error('Unsupported session driver: ' . $sessionDriver);
                break;
        }
    }

    protected function clearFileSessions()
    {
        $sessionPath = storage_path('framework/sessions');

        if (File::exists($sessionPath)) {
            File::cleanDirectory($sessionPath);
            $this->info('All file-based user sessions have been cleared.');
        } else {
            $this->error('Session directory does not exist.');
        }
    }

    protected function clearDatabaseSessions()
    {
        DB::table('sessions')->truncate();
        $this->info('All database-based user sessions have been cleared.');
    }

    protected function clearRedisSessions()
    {
        Redis::connection()->flushdb();
        $this->info('All Redis-based user sessions have been cleared.');
    }
}