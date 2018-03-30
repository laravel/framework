<?php

namespace Illuminate\Contracts\Filesystem;

interface WriteStream
{
    /**
     * Write a new file using a stream.
     *
     * @param  string  $path
     * @param  resource $resource
     * @param  mixed  $options
     * @return bool
     *
     * @throws \InvalidArgumentException If $resource is not a file handle.
     * @throws FileExistsException
     */
    public function writeStream($path, $resource, array $options = []);
}
