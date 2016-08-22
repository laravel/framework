<?php

namespace Illuminate\Http;

use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFile extends SymfonyUploadedFile
{
    use FileHelpers, Macroable;

    /**
     * Store the uploaded file on a filesystem disk.
     *
     * @param  string  $path
     * @param  string|null  $disk
     * @return string|false
     */
    public function store($path, $disk = null)
    {
        return $this->storeAs($path, $this->hashName(), $disk);
    }

    /**
     * Store the uploaded file on a filesystem disk with public visibility.
     *
     * @param  string  $path
     * @param  string|null  $disk
     * @return string|false
     */
    public function storePublicly($path, $disk = null)
    {
        return $this->storeAs($path, $this->hashName(), $disk, 'public');
    }

    /**
     * Store the uploaded file on a filesystem disk with public visibility.
     *
     * @param  string  $path
     * @param  string  $name
     * @param  string|null  $disk
     * @return string|false
     */
    public function storePubliclyAs($path, $name, $disk = null)
    {
        return $this->storeAs($path, $name, $disk, 'public');
    }

    /**
     * Store the uploaded file on a filesystem disk.
     *
     * @param  string  $path
     * @param  string  $name
     * @param  string|null  $disk
     * @param  string|null  $visibility
     * @return string|false
     */
    public function storeAs($path, $name, $disk = null, $visibility = null)
    {
        $factory = Container::getInstance()->make(FilesystemFactory::class);

        return $factory->disk($disk)->putFileAs($path, $this, $name, $visibility);
    }

    /**
     * Create a new file instance from a base instance.
     *
     * @param  \Symfony\Component\HttpFoundation\File\UploadedFile  $file
     * @param  bool $test
     * @return static
     */
    public static function createFromBase(SymfonyUploadedFile $file, $test = false)
    {
        return $file instanceof static ? $file : new static(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getClientSize(),
            $file->getError(),
            $test
        );
    }
}
