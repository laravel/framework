<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_pad;
use function array_values;
use function base64_decode;
use function explode;
use function str_split;

#[AsCommand(name: 'crypt:re-encrypt')]
class ReEncryptCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'crypt:re-encrypt
                    {targets : The table name and columns to re-encrypt, like "users:private,notes"}
                    {key : The new key to use for re-encryption.}
                    {cipher? : The encryption algorithm for re-encryption.}
                    {--old-key : The old key used to encrypt the original values.}
                    {--old-cipher : The old cipher used to encrypt the original values.}
                    {--connection : The database connection name to use.}
                    {--id=id : The column ID to use for lazy chunking.}
                    {--skip-failed : Whether re-encryption should skip any decryption errors.}
                    {--chunk=1000 : The amount of items per chunk to process.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-encrypts a model encrypted columns with a new encryption key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Using "...'.substr($this->argument('key'), -6).'" key to re-encryption');
        $this->newLine();

        $originalEncrypter = $this->makeEncrypter(
            $this->option('old-key') ?: $this->laravel->make('config')->get('app.key'),
            $this->option('old-cipher')
        );

        $newEncrypter = $this->makeEncrypter(
            $this->argument('key'),
            $this->argument('cipher')
        );

        [$table, $columns, $id] = $this->parseOptions();

        $query = $this->laravel->make('db')->connection($this->option('connection'))->table($table);

        $this->withProgressBar(
            $this->rows($query, $columns, $id),
            function (object $row) use ($originalEncrypter, $newEncrypter, $id, $columns, $query) {
                $data = [];

                foreach ($columns as $column) {
                    if (is_null($row->{$column})) {
                        continue;
                    }

                    try {
                        $data[$column] = $originalEncrypter->decrypt($row->{$column}, false);
                    } catch (DecryptException $exception) {
                        if ($this->option('skip-failed')) {
                            continue;
                        }

                        throw $exception;
                    }

                    $data[$column] = $newEncrypter->encrypt($data[$column], false);
                }

                $query->where($id, $row->{$id})->update($data);
            }
        );
    }

    /**
     * Create a new encrypter instance.
     *
     * @return \Illuminate\Encryption\Encrypter
     */
    protected function makeEncrypter($key, $cipher)
    {
        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = Str::after($key, $prefix);
        }

        return new Encrypter(
            base64_decode($key),
            $cipher ?: $this->laravel->make('config')->get('app.cipher')
        );
    }

    /**
     * Parse the options of the command.
     *
     * @return array{table: string, columns: string[], id: string}
     */
    protected function parseOptions()
    {
        [$table, $columns] = array_pad(explode(':', trim($this->argument('targets')), 2), 2, null);

        $columns = explode(',', $columns);

        return [$table, $columns, $this->option('id')];
    }

    /**
     * Create a lazy query for the target model
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string[]  $columns
     * @param  string  $id
     * @return \Illuminate\Support\LazyCollection<object>
     */
    protected function rows($query, $columns, $id)
    {
        return $query
            ->select([$id, ...$columns])
            ->lazyById($this->option('chunk'), $id);
    }

    /**
     * Re-encrypts the model attribute with a new encryption key.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  EncrypterContract  $encrypter
     * @param  array<string,int>  $encryptables
     * @return void
     */
    protected function reEncryptModelValues($model, $encrypter, $encryptables)
    {
        foreach ($encryptables as $name => $value) {
            try {
                $encryptables[$name] = $model->getAttribute($name);
            } catch (DecryptException $exception) {
                if ($this->argument('skip-failed')) {
                    continue;
                }

                throw $exception;
            }
        }
    }

    /**
     * Get the model casts that should be encrypted.
     *
     * @return string[]
     */
    protected function encryptables()
    {
        return array_keys(
            array_filter($this->newModel()->getCasts(), function ($cast) {
                if (is_object($cast) || class_exists($cast)) {
                    $cast = class_basename($cast);
                }

                return Str::startsWith($cast, ['AsEncrypted', 'encrypted']);
            })
        );
    }

    /**
     * Create a new Eloquent Model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function newModel()
    {
        $model = trim($this->argument('model'));

        if (!class_exists($model) && is_dir(app_path('Models'))) {
            $model = Str::start($model, $this->laravel->getNamespace().'\Models\\');
        }

        return new $model;
    }
}
