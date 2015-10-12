<?php

namespace Illuminate\Session;

use SessionHandlerInterface;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;

class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path where sessions should be stored.
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new file driven handler instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->path = $path;
        $this->files = $files;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($this->files->exists($path = $this->path.'/'.$sessionId)) {
            return $this->files->get($path);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->files->put($this->path.'/'.$sessionId, $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->files->delete($this->path.'/'.$sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        $files = Finder::create()
                    ->in($this->path)
                    ->files()
                    ->ignoreDotFiles(true)
                    ->date('<= now - '.$lifetime.' seconds');

        foreach ($files as $file) {
            $this->files->delete($file->getRealPath());
        }
    }
}
