<?php

namespace Illuminate\Tests\Filesystem;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;

use function Illuminate\Filesystem\join_paths;

class JoinPathsHelperTest extends TestCase
{
    #[RequiresOperatingSystem('Linux|DAR')]
    #[DataProvider('unixDataProvider')]
    public function testItCanMergePathsForUnix(string $expected, string $given)
    {
        $this->assertSame($expected, $given);
    }

    public static function unixDataProvider()
    {
        yield ['app/Http/Kernel.php', join_paths('app', 'Http', 'Kernel.php')];
        yield ['app/Http/Kernel.php', join_paths('app', '', 'Http', 'Kernel.php')];
    }

    #[RequiresOperatingSystem('Windows')]
    #[DataProvider('windowsDataProvider')]
    public function testItCanMergePathsForWindows(string $expected, string $given)
    {
        $this->assertSame($expected, $given);
    }

    public static function windowsDataProvider()
    {
        yield ['app\Http\Kernel.php', join_paths('app', 'Http', 'Kernel.php')];
        yield ['app\Http\Kernel.php', join_paths('app', '', 'Http', 'Kernel.php')];
    }
}
