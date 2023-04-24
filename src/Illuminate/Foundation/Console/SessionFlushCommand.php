<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[AsCommand(name: 'session:flush')]
class SessionFlushCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'session:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all user sessions';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $driver = config('session.driver');
        $method_name = 'clean' . ucfirst($driver);
        if ( method_exists($this, $method_name) ) {
            try {
                $this->$method_name();
                $this->components->info('Session data cleaned.');
            } catch (\Exception $e) {
                $this->components->error($e->getMessage());
            }
        } else {
            $this->components->error("Unable to clean the sessions of the driver '{$driver}'.");
        }
    }
    //file
    protected function cleanFile () {
        $directory = config('session.files');
        $ignoreFiles = ['.gitignore', '.', '..'];

        $files = scandir($directory);

        foreach ( $files as $file ) {
            if( !in_array($file,$ignoreFiles) ) {
                unlink($directory . '/' . $file);
            }
        }
    }
    //database
    protected function cleanDatabase () {
        $table = config('session.table');
        DB::table($table)->truncate();
    }
    //cookie
    protected function cleanCookie(){
        throw new \Exception("Session driver 'cookie' cant be flushed");
    }
    //Redis
    protected function cleanRedis(){
        Cache::store("redis")->flush();
    }
    //Memcached
    protected function cleanMemcached(){
        Cache::store("memcached")->flush();
    }
}
