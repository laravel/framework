<?php

namespace Illuminate\Http;

use Illuminate\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * @var string
     */
    protected $hashName = null;

    /**
     * Get the fully qualified path to the file.
     *
     * @return string
     */
    public function path()
    {
        return $this->getRealPath();
    }

    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension()
    {
        return $this->guessExtension();
    }

    /**
     * Get the file's extension supplied by the client.
     *
     * @return string
     */
    public function clientExtension()
    {
        return $this->guessClientExtension();
    }

    /**
     * Get a filename for the file.
     *
     * @param  string  $path
     * @return string
     */
    public function hashName($path = null)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }

        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        return $path.$hash.'.'.$this->guessExtension();
    }

    /**
     * Get a fixed hash filename from same file, expecially for a file which is  uploaded many times.
     * -- by wg
     *
     * @param null $path
     * @return string
     */
    public function fixedHashName($path = null)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }

        $hash = sha1_file($this->path());

        return $path.$hash.'.'.$this->guessExtension();
    }
}
