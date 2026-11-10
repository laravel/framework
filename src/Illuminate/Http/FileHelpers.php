<?php

namespace Illuminate\Http;

use Illuminate\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * @var string|null
     */
    protected $hashName = null;

    /**
     * Get the fully-qualified path to the file.
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
     * @return string|null
     */
    public function extension()
    {
        return $this->guessExtension();
    }

    /**
     * Get a filename for the file.
     *
     * @param  string|null  $path
     * @return string
     */
    public function hashName($path = null)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }

        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        if ($extension = $this->guessExtension()) {
            $extension = '.'.$extension;
        }

        return $path.$hash.$extension;
    }

    /**
     * Get the dimensions of the image (if applicable).
     *
     * @return array|false|null
     */
    public function dimensions()
    {
        $path = $this->getRealPath();

        if ($path && $this->jpegHasTooManySegments($path)) {
            return false;
        }

        return @getimagesize($path);
    }

    /**
     * Determine if a JPEG file contains more segments than the given limit.
     *
     * Crafted JPEG files with hundreds of thousands of minimal APP segments
     * cause PHP's getimagesize() to spend O(n) time scanning them, which
     * can stall workers under concurrent upload requests. Legitimate camera
     * and encoder output rarely exceeds a few dozen segments.
     *
     * @param  string  $path
     * @param  int  $limit
     * @return bool
     */
    protected function jpegHasTooManySegments(string $path, int $limit = 1000): bool
    {
        $fh = @fopen($path, 'rb');

        if ($fh === false) {
            return false;
        }

        if (fread($fh, 2) !== "\xFF\xD8") {
            fclose($fh);

            return false;
        }

        $count = 0;

        while (! feof($fh)) {
            if (fread($fh, 1) !== "\xFF") {
                break;
            }

            // The JPEG spec allows multiple 0xFF padding bytes before a marker.
            do {
                $type = fread($fh, 1);
            } while ($type === "\xFF");

            if ($type === false || $type === '') {
                break;
            }

            $typeByte = ord($type);

            if ($typeByte === 0xD9) {
                break; // EOI
            }

            // SOI and RST0–RST7 carry no length field.
            if ($typeByte === 0xD8 || ($typeByte >= 0xD0 && $typeByte <= 0xD7)) {
                continue;
            }

            // SOS is followed by compressed scan data; nothing useful to count after this.
            if ($typeByte === 0xDA) {
                break;
            }

            $lenBytes = fread($fh, 2);

            if (strlen($lenBytes) < 2) {
                break;
            }

            if (++$count > $limit) {
                fclose($fh);

                return true;
            }

            $segLen = unpack('n', $lenBytes)[1] - 2;

            if ($segLen > 0) {
                fseek($fh, $segLen, SEEK_CUR);
            }
        }

        fclose($fh);

        return false;
    }
}
