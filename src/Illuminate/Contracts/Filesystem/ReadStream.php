<?php

namespace Illuminate\Contracts\Filesystem;

interface ReadStream
{
    /**
     * Get a resource to read the file.
     *
     * @param  string  $path
     * @return resource|null The path resource or null on failure.
     *
     * @throws FileNotFoundException
     */
    public function readStream($path);
}
