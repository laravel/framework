<?php

namespace Illuminate\Config;

use Exception;
use ArrayAccess;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;

class Repository implements ArrayAccess, ConfigContract
{
    use Macroable;

    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Configs loaded from cache
     *
     * @var boolean
     */
    private bool $loadedFromCache = false;

    /**
     * Path of configuration files
     *
     * @var string
     */
    private string $configPath;

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     * @param  string  $configPath
     * @throws Exception
     */
    public function __construct(array $items = [], string $configPath = 'test')
    {
        $this->configPath = $configPath;

        if ($items) {
            $this->items           = $items;
            $this->loadedFromCache = true;
        }

        if (!$configPath == 'test' && !$this->get('app')) {
            throw new Exception('Unable to load the "app" configuration file.');
        }
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        if (is_array($key)) {
            return $this->hasMany($key);
        }

        $this->load($key);
        return Arr::has($this->items, $key);
    }

    /**
     * Check many keys has on configuration items.
     * this added for load file before checking
     *
     * @param  array  $keys
     * @return bool
     */
    public function hasMany(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->load($key);
            if (!Arr::has($this->items, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        $this->load($key);
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $this->load($key);
            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->load($key);
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->set($key, null);
    }

    /**
     * Load config file when not loaded or cache not loaded
     *
     * @param  string|int  $key
     * @return void
     */
    private function load($key): void
    {
        if (!$this->loadedFromCache && (is_string($key) || is_int($key)) && $file = $this->getConfigurationFile("" . $key)) {
            if (!Arr::has($this->items, $file[0])) {
                Arr::set($this->items, $file[0], require $file[1]);
            }
        }
    }

    /**
     * Get config file key and file path
     *
     * @param  string  $key
     * @param  string  $fileKey
     * @param  string|null  $configPath
     * @return array
     */
    private function getConfigurationFile(string $key, string $fileKey = '', string $configPath = null): array
    {
        $aKey = explode('.', $key, 2);

        if (!$configPath) {
            $configPath = realpath($this->configPath);
        }

        $fileKey   .= $fileKey ? "." . $aKey[0] : $aKey[0];
        $directory = $configPath . DIRECTORY_SEPARATOR . $aKey[0];

        //this for check on nests directories
        if (count($aKey) > 1 && is_dir($directory) && $file = $this->getConfigurationFile($aKey[1], $fileKey, $directory)) {
            //This part is for when we have a directory and a file
            // with the same name, the file is loaded first and
            // then the files inside the folder are loaded.
            if (is_file($directory . ".php")) {
                $this->load($fileKey);
            }
            return $file;
        } elseif (is_file($directory . ".php")) {
            return [$fileKey, $directory . ".php"];
        }
        return [];
    }
}
