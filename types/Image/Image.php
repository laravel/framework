<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image;

use function PHPStan\Testing\assertType;

$file = UploadedFile::fake()->image('test.jpg', 100, 100);
$image = new Image(file_get_contents($file->getRealPath()));

rescue(fn () => assertType('never', $image->resize()));
rescue(fn () => assertType('never', $image->scale()));
rescue(fn () => assertType('never', $image->optimize('gibberish')));
rescue(fn () => assertType('never', $image->toFormat('gibberish')));
