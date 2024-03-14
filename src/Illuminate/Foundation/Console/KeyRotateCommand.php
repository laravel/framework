<?php

namespace Illuminate\Foundation\Console;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'key:rotate')]
class KeyRotateCommand extends KeyGenerateCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:rotate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate the application key.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        $previousKeys = $this->laravel['config']['app.previous_keys'];
        $previousKeys[] = $this->laravel['config']['app.key'];

        $keys = [
            'app.key' => $key,
            'app.previous_keys' => $previousKeys,
        ];

        if ($this->option('show')) {
            $this->line('<comment>'.$key.'</comment>');
        }

        if (! $this->setKeysInEnviornmentFile($keys)) {
            return;
        }

        $this->laravel['config']['app.key'] = $key;
        $this->laravel['config']['app.previous_keys'] = $previousKeys;

        $this->components->info('Appliction key successfully rotated');
    }

    protected function setKeysInEnviornmentFile($keys)
    {
        $input = file_get_contents($this->laravel->environmentFilePath());

        foreach ($keys as $key => $value) {
            try {
                $input = $this->prepareEnviornmentInputWith($key, $value, $input);
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                return false;
            }

            $this->laravel['config'][$key] = $value;
        }

        file_put_contents($this->laravel->environmentFilePath(), $input);

        return true;
    }

    protected function prepareEnviornmentInputWith($key, $value, $input)
    {
        $keyName = $this->enviornmentKeyName($key);

        $keyValue = is_array($value)
            ? implode(',', $value)
            : $value;

        $replaced = preg_replace(
            $this->keyReplacementPattern($key),
            $keyName.'='.$keyValue,
            $input
        );

        if ($replaced === $input || $replaced === null) {
            throw new \Exception("Unable to set key {$keyName}, No {$keyName} found in the .env file");
        }

        return $replaced;
    }
}
