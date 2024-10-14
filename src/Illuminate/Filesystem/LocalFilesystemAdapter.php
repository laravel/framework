<?php

namespace Illuminate\Filesystem;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use RuntimeException;

class LocalFilesystemAdapter extends FilesystemAdapter
{
    use Conditionable;

    /**
     * The name of the filesystem disk.
     *
     * @var string
     */
    protected $disk;

    /**
     * Indicates if signed URLs should serve corresponding files.
     *
     * @var bool
     */
    protected $shouldServeSignedUrls = false;

    /**
     * The Closure that should be used to resolve the URL generator.
     *
     * @var \Closure
     */
    protected $urlGeneratorResolver;

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUrls()
    {
        return $this->temporaryUrlCallback || (
            $this->shouldServeSignedUrls && $this->urlGeneratorResolver instanceof Closure
        );
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        if ($this->temporaryUrlCallback) {
            return $this->temporaryUrlCallback->bindTo($this, static::class)(
                $path, $expiration, $options
            );
        }

        if (! $this->providesTemporaryUrls()) {
            throw new RuntimeException('This driver does not support creating temporary URLs.');
        }

        $url = call_user_func($this->urlGeneratorResolver);

        return $url->to($url->temporarySignedRoute(
            'storage.'.$this->disk,
            $expiration,
            ['path' => $path],
            absolute: false
        ));
    }

    /**
     * Specify the name of the disk the adapter is managing.
     *
     * @param  string  $disk
     * @return $this
     */
    public function diskName(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Indiate that signed URLs should serve the corresponding files.
     *
     * @param  bool  $serve
     * @param  \Closure|null  $urlGeneratorResolver
     * @return $this
     */
    public function shouldServeSignedUrls(bool $serve = true, ?Closure $urlGeneratorResolver = null)
    {
        $this->shouldServeSignedUrls = $serve;
        $this->urlGeneratorResolver = $urlGeneratorResolver;

        return $this;
    }
}
