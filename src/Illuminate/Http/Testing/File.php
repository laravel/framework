<?php

namespace Illuminate\Http\Testing;

use Illuminate\Http\UploadedFile;

class File extends UploadedFile
{
    /**
     * The name of the file.
     *
     * @var string
     */
    public $name;

    /**
     * The temporary file resource.
     *
     * @var resource
     */
    public $tempFile;

    /**
     * The "size" to report.
     *
     * @var int
     */
    public $sizeToReport;

    /**
     * Create a new file instance.
     *
     * @param  string  $name
     * @param  resource  $tempFile
     * @return void
     */
    public function __construct($name, $tempFile)
    {
        $this->name = $name;
        $this->tempFile = $tempFile;

        parent::__construct(
            $this->tempFilePath(), $name, $this->getMimeType(),
            filesize($this->tempFilePath()), null, true
        );
    }

    /**
     * Create a new fake file.
     *
     * @param  string  $name
     * @param  int  $kilobytes
     * @return \Illuminate\Http\Testing\File
     */
    public static function create($name, $kilobytes = 0)
    {
        return (new FileFactory)->create($name, $kilobytes);
    }

    /**
     * Create a new fake image.
     *
     * @param  string  $name
     * @param  int  $width
     * @param  int  $height
     * @return \Illuminate\Http\Testing\File
     */
    public static function image($name, $width = 10, $height = 10)
    {
        return (new FileFactory)->image($name, $width, $height);
    }

    /**
     * Set the "size" of the file in kilobytes.
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function size($kilobytes)
    {
        $this->sizeToReport = $kilobytes * 1024;

        return $this;
    }

    /**
     * Get the size of the file.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->sizeToReport ?: parent::getSize();
    }

    /**
     * Get the MIME type for the file.
     *
     * @return string
     */
    public function getMimeType()
    {
        return MimeType::from($this->name);
    }

    /**
     * Get the path to the temporary file.
     *
     * @return string
     */
    protected function tempFilePath()
    {
        return stream_get_meta_data($this->tempFile)['uri'];
    }
}
