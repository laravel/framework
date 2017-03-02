<?php

namespace Illuminate\Http\Testing;

class FileFactory
{
    /**
     * Create a new fake file.
     *
     * @param  string  $name
     * @param  int  $kilobytes
     * @return \Illuminate\Http\Testing\File
     */
    public function create($name, $kilobytes = 0)
    {
        return tap(new File($name, tmpfile()), function ($file) use ($kilobytes) {
            $file->sizeToReport = $kilobytes * 1024;
        });
    }

    /**
     * Create a new fake image.
     *
     * @param  string  $name
     * @param  int  $width
     * @param  int  $height
     * @return \Illuminate\Http\Testing\File
     */
    public function image($name, $width = 10, $height = 10)
    {
        return new File($name, $this->generateImage($width, $height));
    }

    /**
     * Generate a dummy image of the given width and height.
     *
     * @param  int  $width
     * @param  int  $height
     * @return resource
     */
    protected function generateImage($width, $height)
    {
        return tap(tmpfile(), function ($temp) use ($width, $height) {
            imagepng(imagecreatetruecolor($width, $height), $temp);
        });
    }
}
