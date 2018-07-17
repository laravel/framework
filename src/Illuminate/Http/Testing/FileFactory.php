<?php

namespace Illuminate\Http\Testing;

use Illuminate\Support\Str;

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
        return new File($name, $this->generateImage(
            $width, $height, Str::endsWith(Str::lower($name), ['.jpg', '.jpeg']) ? 'jpeg' : 'png'
        ));
    }

    /**
     * Generate a dummy image of the given width and height.
     *
     * @param  int  $width
     * @param  int  $height
     * @param  string  $type
     * @return resource
     */
    protected function generateImage($width, $height, $type)
    {
        return tap(tmpfile(), function ($temp) use ($width, $height, $type) {
            ob_start();

            $image = imagecreatetruecolor($width, $height);

            switch ($type) {
                case 'jpeg':
                    imagejpeg($image);
                    break;
                case 'png':
                    imagepng($image);
                    break;
            }

            fwrite($temp, ob_get_clean());
        });
    }
}
