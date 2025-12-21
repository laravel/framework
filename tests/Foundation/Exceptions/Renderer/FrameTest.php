<?php

namespace Illuminate\Tests\Foundation\Exceptions\Renderer;

use Illuminate\Foundation\Exceptions\Renderer\Frame;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class FrameTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    #[DataProvider('unixFileDataProvider')]
    public function test_it_normalizes_file_path_on_unix($frameData, $basePath, $expected)
    {
        $exception = m::mock(FlattenException::class);
        $classMap = [];
        $frame = new Frame($exception, $classMap, $frameData, $basePath);

        $this->assertEquals($expected, $frame->file());
    }

    public static function unixFileDataProvider()
    {
        yield 'internal function' => [
            ['line' => 10],
            '/path/to/your-app',
            '[internal function]',
        ];
        yield 'unknown file' => [
            ['file' => 123, 'line' => 10],
            '/path/to/your-app',
            '[unknown file]',
        ];
        yield 'file with base path' => [
            ['file' => '/path/to/your-app/app/Http/Controllers/UserController.php', 'line' => 10],
            '/path/to/your-app',
            'app/Http/Controllers/UserController.php',
        ];
        yield 'file without base path' => [
            ['file' => '/other/path/app/Http/Controllers/UserController.php', 'line' => 10],
            '/path/to/your-app',
            '/other/path/app/Http/Controllers/UserController.php',
        ];
    }

    #[RequiresOperatingSystem('Windows')]
    #[DataProvider('windowsFileDataProvider')]
    public function test_it_normalizes_file_path_on_windows($frameData, $basePath, $expected)
    {
        $exception = m::mock(FlattenException::class);
        $classMap = [];
        $frame = new Frame($exception, $classMap, $frameData, $basePath);

        $this->assertEquals($expected, $frame->file());
    }

    public static function windowsFileDataProvider()
    {
        yield 'internal function' => [
            ['line' => 10],
            'C:\path\to\your-app',
            '[internal function]',
        ];
        yield 'unknown file' => [
            ['file' => 123, 'line' => 10],
            'C:\path\to\your-app',
            '[unknown file]',
        ];
        yield 'file with base path' => [
            ['file' => 'C:\path\to\your-app\app\Http\Controllers\UserController.php', 'line' => 10],
            'C:\path\to\your-app',
            'app\Http\Controllers\UserController.php',
        ];
        yield 'file without base path' => [
            ['file' => 'D:\other\path\app\Http\Controllers\UserController.php', 'line' => 10],
            'C:\path\to\your-app',
            'D:\other\path\app\Http\Controllers\UserController.php',
        ];
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    #[DataProvider('unixIsFromVendorDataProvider')]
    public function test_it_determines_if_frame_is_from_vendor_on_unix($frameData, $basePath, $expected)
    {
        $exception = m::mock(FlattenException::class);
        $classMap = [];
        $frame = new Frame($exception, $classMap, $frameData, $basePath);

        $this->assertEquals($expected, $frame->isFromVendor());
    }

    public static function unixIsFromVendorDataProvider()
    {
        yield 'vendor file' => [
            ['file' => '/path/to/your-app/vendor/laravel/framework/src/File.php', 'line' => 10],
            '/path/to/your-app',
            true,
        ];
        yield 'app file' => [
            ['file' => '/path/to/your-app/app/Models/User.php', 'line' => 10],
            '/path/to/your-app',
            false,
        ];
        yield 'outside base path' => [
            ['file' => '/other/path/file.php', 'line' => 10],
            '/path/to/your-app',
            true,
        ];
        yield 'vendor in filename' => [
            ['file' => '/path/to/your-app/app/vendorfile.php', 'line' => 10],
            '/path/to/your-app',
            false,
        ];
    }

    #[RequiresOperatingSystem('Windows')]
    #[DataProvider('windowsIsFromVendorDataProvider')]
    public function test_it_determines_if_frame_is_from_vendor_on_windows($frameData, $basePath, $expected)
    {
        $exception = m::mock(FlattenException::class);
        $classMap = [];
        $frame = new Frame($exception, $classMap, $frameData, $basePath);

        $this->assertEquals($expected, $frame->isFromVendor());
    }

    public static function windowsIsFromVendorDataProvider()
    {
        yield 'vendor file' => [
            ['file' => 'C:\path\to\your-app\vendor\laravel\framework\src\File.php', 'line' => 10],
            'C:\path\to\your-app',
            true,
        ];
        yield 'app file' => [
            ['file' => 'C:\path\to\your-app\app\Models\User.php', 'line' => 10],
            'C:\path\to\your-app',
            false,
        ];
        yield 'outside base path' => [
            ['file' => 'D:\other\path\file.php', 'line' => 10],
            'C:\path\to\your-app',
            true,
        ];
        yield 'vendor in filename' => [
            ['file' => 'C:\path\to\your-app\app\vendorfile.php', 'line' => 10],
            'C:\path\to\your-app',
            false,
        ];
    }
}
