<?php

namespace Illuminate\Tests\Filesystem;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\LazyCollection;
use Illuminate\Testing\Assert;
use Mockery as m;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class FilesystemTest extends TestCase
{
    private static $tempDir;

    #[BeforeClass]
    public static function setUpTempDir()
    {
        self::$tempDir = sys_get_temp_dir().'/tmp';
        mkdir(self::$tempDir);
    }

    #[AfterClass]
    public static function tearDownTempDir()
    {
        $files = new Filesystem;
        $files->deleteDirectory(self::$tempDir);
        self::$tempDir = null;
    }

    protected function tearDown(): void
    {
        m::close();

        $files = new Filesystem;
        $files->deleteDirectory(self::$tempDir, $preserve = true);
    }

    public function testGetRetrievesFiles()
    {
        file_put_contents(self::$tempDir.'/file.txt', 'Hello World');
        $files = new Filesystem;
        $this->assertSame('Hello World', $files->get(self::$tempDir.'/file.txt'));
    }

    public function testPutStoresFiles()
    {
        $files = new Filesystem;
        $files->put(self::$tempDir.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile(self::$tempDir.'/file.txt', 'Hello World');
    }

    public function testLines()
    {
        $path = self::$tempDir.'/file.txt';

        $contents = ' '.PHP_EOL.' spaces around '.PHP_EOL.PHP_EOL.'Line 2'.PHP_EOL.'1 trailing empty line ->'.PHP_EOL.PHP_EOL;
        file_put_contents($path, $contents);

        $files = new Filesystem;
        $this->assertInstanceOf(LazyCollection::class, $files->lines($path));

        $this->assertSame(
            [' ', ' spaces around ', '', 'Line 2', '1 trailing empty line ->', '', ''],
            $files->lines($path)->all()
        );

        // an empty file:
        ftruncate(fopen($path, 'w'), 0);
        $this->assertSame([''], $files->lines($path)->all());
    }

    public function testLinesThrowsExceptionNonexisitingFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File does not exist at path '.__DIR__.'/unknown-file.txt.');

        (new Filesystem)->lines(__DIR__.'/unknown-file.txt');
    }

    public function testReplaceCreatesFile()
    {
        $tempFile = self::$tempDir.'/file.txt';

        $filesystem = new Filesystem;

        $filesystem->replace($tempFile, 'Hello World');
        $this->assertStringEqualsFile($tempFile, 'Hello World');
    }

    public function testReplaceInFileCorrectlyReplaces()
    {
        $tempFile = self::$tempDir.'/file.txt';

        $filesystem = new Filesystem;

        $filesystem->put($tempFile, 'Hello World');
        $filesystem->replaceInFile('Hello World', 'Hello Taylor', $tempFile);
        $this->assertStringEqualsFile($tempFile, 'Hello Taylor');
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testReplaceWhenUnixSymlinkExists()
    {
        $tempFile = self::$tempDir.'/file.txt';
        $symlinkDir = self::$tempDir.'/symlink_dir';
        $symlink = "{$symlinkDir}/symlink.txt";

        mkdir($symlinkDir);
        symlink($tempFile, $symlink);

        // Prevent changes to symlink_dir
        chmod($symlinkDir, 0555);

        // Test with a weird non-standard umask.
        $umask = 0131;
        $originalUmask = umask($umask);

        $filesystem = new Filesystem;

        // Test replacing non-existent file.
        $filesystem->replace($tempFile, 'Hello World');
        $this->assertStringEqualsFile($tempFile, 'Hello World');
        $this->assertEquals($umask, 0777 - $this->getFilePermissions($tempFile));

        // Test replacing existing file.
        $filesystem->replace($tempFile, 'Something Else');
        $this->assertStringEqualsFile($tempFile, 'Something Else');
        $this->assertEquals($umask, 0777 - $this->getFilePermissions($tempFile));

        // Test replacing symlinked file.
        $filesystem->replace($symlink, 'Yet Something Else Again');
        $this->assertStringEqualsFile($tempFile, 'Yet Something Else Again');
        $this->assertEquals($umask, 0777 - $this->getFilePermissions($tempFile));

        umask($originalUmask);

        // Reset changes to symlink_dir
        chmod($symlinkDir, 0777 - $originalUmask);
    }

    public function testSetChmod()
    {
        file_put_contents(self::$tempDir.'/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->chmod(self::$tempDir.'/file.txt', 0755);
        $filePermission = substr(sprintf('%o', fileperms(self::$tempDir.'/file.txt')), -4);
        $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';
        $this->assertEquals($expectedPermissions, $filePermission);
    }

    public function testGetChmod()
    {
        file_put_contents(self::$tempDir.'/file.txt', 'Hello World');
        chmod(self::$tempDir.'/file.txt', 0755);

        $files = new Filesystem;
        $filePermission = $files->chmod(self::$tempDir.'/file.txt');
        $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';
        $this->assertEquals($expectedPermissions, $filePermission);
    }

    public function testDeleteRemovesFiles()
    {
        file_put_contents(self::$tempDir.'/file1.txt', 'Hello World');
        file_put_contents(self::$tempDir.'/file2.txt', 'Hello World');
        file_put_contents(self::$tempDir.'/file3.txt', 'Hello World');

        $files = new Filesystem;
        $files->delete(self::$tempDir.'/file1.txt');
        Assert::assertFileDoesNotExist(self::$tempDir.'/file1.txt');

        $files->delete([self::$tempDir.'/file2.txt', self::$tempDir.'/file3.txt']);
        Assert::assertFileDoesNotExist(self::$tempDir.'/file2.txt');
        Assert::assertFileDoesNotExist(self::$tempDir.'/file3.txt');
    }

    public function testPrependExistingFiles()
    {
        $files = new Filesystem;
        $files->put(self::$tempDir.'/file.txt', 'World');
        $files->prepend(self::$tempDir.'/file.txt', 'Hello ');
        $this->assertStringEqualsFile(self::$tempDir.'/file.txt', 'Hello World');
    }

    public function testPrependNewFiles()
    {
        $files = new Filesystem;
        $files->prepend(self::$tempDir.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile(self::$tempDir.'/file.txt', 'Hello World');
    }

    public function testMissingFile()
    {
        $files = new Filesystem;
        $this->assertTrue($files->missing(self::$tempDir.'/file.txt'));
    }

    public function testDeleteDirectory()
    {
        mkdir(self::$tempDir.'/foo');
        file_put_contents(self::$tempDir.'/foo/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->deleteDirectory(self::$tempDir.'/foo');
        Assert::assertDirectoryDoesNotExist(self::$tempDir.'/foo');
        Assert::assertFileDoesNotExist(self::$tempDir.'/foo/file.txt');
    }

    public function testDeleteDirectoryReturnFalseWhenNotADirectory()
    {
        mkdir(self::$tempDir.'/bar');
        file_put_contents(self::$tempDir.'/bar/file.txt', 'Hello World');
        $files = new Filesystem;
        $this->assertFalse($files->deleteDirectory(self::$tempDir.'/bar/file.txt'));
    }

    public function testCleanDirectory()
    {
        mkdir(self::$tempDir.'/baz');
        file_put_contents(self::$tempDir.'/baz/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->cleanDirectory(self::$tempDir.'/baz');
        $this->assertDirectoryExists(self::$tempDir.'/baz');
        Assert::assertFileDoesNotExist(self::$tempDir.'/baz/file.txt');
    }

    public function testMacro()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'Hello World');
        $files = new Filesystem;
        $tempDir = self::$tempDir;
        $files->macro('getFoo', function () use ($files, $tempDir) {
            return $files->get($tempDir.'/foo.txt');
        });
        $this->assertSame('Hello World', $files->getFoo());
    }

    public function testFilesMethod()
    {
        mkdir(self::$tempDir.'/views');
        file_put_contents(self::$tempDir.'/views/1.txt', '1');
        file_put_contents(self::$tempDir.'/views/2.txt', '2');
        mkdir(self::$tempDir.'/views/_layouts');
        $files = new Filesystem;
        $results = $files->files(self::$tempDir.'/views');
        $this->assertInstanceOf(SplFileInfo::class, $results[0]);
        $this->assertInstanceOf(SplFileInfo::class, $results[1]);
        unset($files);
    }

    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $files = new Filesystem;
        $this->assertFalse($files->copyDirectory(self::$tempDir.'/breeze/boom/foo/bar/baz', self::$tempDir));
    }

    public function testCopyDirectoryMovesEntireDirectory()
    {
        mkdir(self::$tempDir.'/tmp', 0777, true);
        file_put_contents(self::$tempDir.'/tmp/foo.txt', '');
        file_put_contents(self::$tempDir.'/tmp/bar.txt', '');
        mkdir(self::$tempDir.'/tmp/nested', 0777, true);
        file_put_contents(self::$tempDir.'/tmp/nested/baz.txt', '');

        $files = new Filesystem;
        $files->copyDirectory(self::$tempDir.'/tmp', self::$tempDir.'/tmp2');
        $this->assertDirectoryExists(self::$tempDir.'/tmp2');
        $this->assertFileExists(self::$tempDir.'/tmp2/foo.txt');
        $this->assertFileExists(self::$tempDir.'/tmp2/bar.txt');
        $this->assertDirectoryExists(self::$tempDir.'/tmp2/nested');
        $this->assertFileExists(self::$tempDir.'/tmp2/nested/baz.txt');
    }

    public function testMoveDirectoryMovesEntireDirectory()
    {
        mkdir(self::$tempDir.'/tmp2', 0777, true);
        file_put_contents(self::$tempDir.'/tmp2/foo.txt', '');
        file_put_contents(self::$tempDir.'/tmp2/bar.txt', '');
        mkdir(self::$tempDir.'/tmp2/nested', 0777, true);
        file_put_contents(self::$tempDir.'/tmp2/nested/baz.txt', '');

        $files = new Filesystem;
        $files->moveDirectory(self::$tempDir.'/tmp2', self::$tempDir.'/tmp3');
        $this->assertDirectoryExists(self::$tempDir.'/tmp3');
        $this->assertFileExists(self::$tempDir.'/tmp3/foo.txt');
        $this->assertFileExists(self::$tempDir.'/tmp3/bar.txt');
        $this->assertDirectoryExists(self::$tempDir.'/tmp3/nested');
        $this->assertFileExists(self::$tempDir.'/tmp3/nested/baz.txt');
        Assert::assertDirectoryDoesNotExist(self::$tempDir.'/tmp2');
    }

    public function testMoveDirectoryMovesEntireDirectoryAndOverwrites()
    {
        mkdir(self::$tempDir.'/tmp4', 0777, true);
        file_put_contents(self::$tempDir.'/tmp4/foo.txt', '');
        file_put_contents(self::$tempDir.'/tmp4/bar.txt', '');
        mkdir(self::$tempDir.'/tmp4/nested', 0777, true);
        file_put_contents(self::$tempDir.'/tmp4/nested/baz.txt', '');
        mkdir(self::$tempDir.'/tmp5', 0777, true);
        file_put_contents(self::$tempDir.'/tmp5/foo2.txt', '');
        file_put_contents(self::$tempDir.'/tmp5/bar2.txt', '');

        $files = new Filesystem;
        $files->moveDirectory(self::$tempDir.'/tmp4', self::$tempDir.'/tmp5', true);
        $this->assertDirectoryExists(self::$tempDir.'/tmp5');
        $this->assertFileExists(self::$tempDir.'/tmp5/foo.txt');
        $this->assertFileExists(self::$tempDir.'/tmp5/bar.txt');
        $this->assertDirectoryExists(self::$tempDir.'/tmp5/nested');
        $this->assertFileExists(self::$tempDir.'/tmp5/nested/baz.txt');
        Assert::assertFileDoesNotExist(self::$tempDir.'/tmp5/foo2.txt');
        Assert::assertFileDoesNotExist(self::$tempDir.'/tmp5/bar2.txt');
        Assert::assertDirectoryDoesNotExist(self::$tempDir.'/tmp4');
    }

    public function testMoveDirectoryReturnsFalseWhileOverwritingAndUnableToDeleteDestinationDirectory()
    {
        mkdir(self::$tempDir.'/tmp6', 0777, true);
        file_put_contents(self::$tempDir.'/tmp6/foo.txt', '');
        mkdir(self::$tempDir.'/tmp7', 0777, true);

        $files = m::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('deleteDirectory')->once()->andReturn(false);
        $this->assertFalse($files->moveDirectory(self::$tempDir.'/tmp6', self::$tempDir.'/tmp7', true));
    }

    public function testGetThrowsExceptionNonexisitingFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File does not exist at path '.self::$tempDir.'/unknown-file.txt.');

        (new Filesystem)->get(self::$tempDir.'/unknown-file.txt');
    }

    public function testGetRequireReturnsProperly()
    {
        file_put_contents(self::$tempDir.'/file.php', '<?php return "Howdy?"; ?>');
        $files = new Filesystem;
        $this->assertSame('Howdy?', $files->getRequire(self::$tempDir.'/file.php'));
    }

    public function testGetRequireThrowsExceptionNonExistingFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File does not exist at path '.self::$tempDir.'/unknown-file.txt.');

        (new Filesystem)->getRequire(self::$tempDir.'/unknown-file.txt');
    }

    public function testJsonReturnsDecodedJsonData()
    {
        file_put_contents(self::$tempDir.'/file.json', '{"foo": "bar"}');
        $files = new Filesystem;
        $this->assertSame(['foo' => 'bar'], $files->json(self::$tempDir.'/file.json'));
    }

    public function testJsonReturnsNullIfJsonDataIsInvalid()
    {
        file_put_contents(self::$tempDir.'/file.json', '{"foo":');
        $files = new Filesystem;
        $this->assertNull($files->json(self::$tempDir.'/file.json'));
    }

    public function testAppendAddsDataToFile()
    {
        file_put_contents(self::$tempDir.'/file.txt', 'foo');
        $files = new Filesystem;
        $bytesWritten = $files->append(self::$tempDir.'/file.txt', 'bar');
        $this->assertEquals(mb_strlen('bar', '8bit'), $bytesWritten);
        $this->assertFileExists(self::$tempDir.'/file.txt');
        $this->assertStringEqualsFile(self::$tempDir.'/file.txt', 'foobar');
    }

    public function testMoveMovesFiles()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $files->move(self::$tempDir.'/foo.txt', self::$tempDir.'/bar.txt');
        $this->assertFileExists(self::$tempDir.'/bar.txt');
        Assert::assertFileDoesNotExist(self::$tempDir.'/foo.txt');
    }

    public function testNameReturnsName()
    {
        file_put_contents(self::$tempDir.'/foobar.txt', 'foo');
        $filesystem = new Filesystem;
        $this->assertSame('foobar', $filesystem->name(self::$tempDir.'/foobar.txt'));
    }

    public function testExtensionReturnsExtension()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertSame('txt', $files->extension(self::$tempDir.'/foo.txt'));
    }

    public function testBasenameReturnsBasename()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertSame('foo.txt', $files->basename(self::$tempDir.'/foo.txt'));
    }

    public function testDirnameReturnsDirectory()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals(self::$tempDir, $files->dirname(self::$tempDir.'/foo.txt'));
    }

    public function testTypeIdentifiesFile()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertSame('file', $files->type(self::$tempDir.'/foo.txt'));
    }

    public function testTypeIdentifiesDirectory()
    {
        mkdir(self::$tempDir.'/foo-dir');
        $files = new Filesystem;
        $this->assertSame('dir', $files->type(self::$tempDir.'/foo-dir'));
    }

    public function testSizeOutputsSize()
    {
        $size = file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals($size, $files->size(self::$tempDir.'/foo.txt'));
    }

    #[RequiresPhpExtension('fileinfo')]
    public function testMimeTypeOutputsMimeType()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertSame('text/plain', $files->mimeType(self::$tempDir.'/foo.txt'));
    }

    public function testIsWritable()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        @chmod(self::$tempDir.'/foo.txt', 0444);
        $this->assertFalse($files->isWritable(self::$tempDir.'/foo.txt'));
        @chmod(self::$tempDir.'/foo.txt', 0777);
        $this->assertTrue($files->isWritable(self::$tempDir.'/foo.txt'));
    }

    public function testIsReadable()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $files = new Filesystem;
        // chmod is noneffective on Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->assertTrue($files->isReadable(self::$tempDir.'/foo.txt'));
        } else {
            @chmod(self::$tempDir.'/foo.txt', 0000);
            $this->assertFalse($files->isReadable(self::$tempDir.'/foo.txt'));
            @chmod(self::$tempDir.'/foo.txt', 0777);
            $this->assertTrue($files->isReadable(self::$tempDir.'/foo.txt'));
        }
        $this->assertFalse($files->isReadable(self::$tempDir.'/doesnotexist.txt'));
    }

    public function testIsDirEmpty()
    {
        mkdir(self::$tempDir.'/foo-dir');
        file_put_contents(self::$tempDir.'/foo-dir/.hidden', 'foo');
        mkdir(self::$tempDir.'/bar-dir');
        file_put_contents(self::$tempDir.'/bar-dir/foo.txt', 'foo');
        mkdir(self::$tempDir.'/baz-dir');
        mkdir(self::$tempDir.'/baz-dir/.hidden');
        mkdir(self::$tempDir.'/quz-dir');
        mkdir(self::$tempDir.'/quz-dir/not-hidden');

        $files = new Filesystem;

        $this->assertTrue($files->isEmptyDirectory(self::$tempDir.'/foo-dir', true));
        $this->assertFalse($files->isEmptyDirectory(self::$tempDir.'/foo-dir'));
        $this->assertFalse($files->isEmptyDirectory(self::$tempDir.'/bar-dir', true));
        $this->assertFalse($files->isEmptyDirectory(self::$tempDir.'/bar-dir'));
        $this->assertTrue($files->isEmptyDirectory(self::$tempDir.'/baz-dir', true));
        $this->assertFalse($files->isEmptyDirectory(self::$tempDir.'/baz-dir'));
        $this->assertFalse($files->isEmptyDirectory(self::$tempDir.'/quz-dir', true));
        $this->assertFalse($files->isEmptyDirectory(self::$tempDir.'/quz-dir'));
    }

    public function testGlobFindsFiles()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        file_put_contents(self::$tempDir.'/bar.txt', 'bar');
        $files = new Filesystem;
        $glob = $files->glob(self::$tempDir.'/*.txt');
        $this->assertContains(self::$tempDir.'/foo.txt', $glob);
        $this->assertContains(self::$tempDir.'/bar.txt', $glob);
    }

    public function testAllFilesFindsFiles()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        file_put_contents(self::$tempDir.'/bar.txt', 'bar');
        $files = new Filesystem;
        $allFiles = [];
        foreach ($files->allFiles(self::$tempDir) as $file) {
            $allFiles[] = $file->getFilename();
        }
        $this->assertContains('foo.txt', $allFiles);
        $this->assertContains('bar.txt', $allFiles);
    }

    public function testDirectoriesFindsDirectories()
    {
        mkdir(self::$tempDir.'/film');
        mkdir(self::$tempDir.'/music');
        $files = new Filesystem;
        $directories = $files->directories(self::$tempDir);
        $this->assertContains(self::$tempDir.DIRECTORY_SEPARATOR.'film', $directories);
        $this->assertContains(self::$tempDir.DIRECTORY_SEPARATOR.'music', $directories);
    }

    public function testMakeDirectory()
    {
        $files = new Filesystem;
        $this->assertTrue($files->makeDirectory(self::$tempDir.'/created'));
        $this->assertFileExists(self::$tempDir.'/created');
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    #[RequiresPhpExtension('pcntl')]
    public function testSharedGet()
    {
        $content = str_repeat('123456', 1000000);
        $result = 1;

        posix_setpgid(0, 0);

        for ($i = 1; $i <= 20; $i++) {
            $pid = pcntl_fork();

            if (! $pid) {
                $files = new Filesystem;
                $files->put(self::$tempDir.'/file.txt', $content, true);
                $read = $files->get(self::$tempDir.'/file.txt', true);

                exit(strlen($read) === strlen($content) ? 1 : 0);
            }
        }

        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
            $result *= $status;
        }

        $this->assertSame(1, $result);
    }

    public function testRequireOnceRequiresFileProperly()
    {
        $filesystem = new Filesystem;
        mkdir(self::$tempDir.'/scripts');
        file_put_contents(self::$tempDir.'/scripts/foo.php', '<?php function random_function_xyz(){};');
        $filesystem->requireOnce(self::$tempDir.'/scripts/foo.php');
        file_put_contents(self::$tempDir.'/scripts/foo.php', '<?php function random_function_xyz_changed(){};');
        $filesystem->requireOnce(self::$tempDir.'/scripts/foo.php');
        $this->assertTrue(function_exists('random_function_xyz'));
        $this->assertFalse(function_exists('random_function_xyz_changed'));
    }

    public function testRequireOnceThrowsExceptionNonexisitingFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File does not exist at path '.__DIR__.'/unknown-file.txt.');

        (new Filesystem)->requireOnce(__DIR__.'/unknown-file.txt');
    }

    public function testCopyCopiesFileProperly()
    {
        $filesystem = new Filesystem;
        $data = 'contents';
        mkdir(self::$tempDir.'/text');
        file_put_contents(self::$tempDir.'/text/foo.txt', $data);
        $filesystem->copy(self::$tempDir.'/text/foo.txt', self::$tempDir.'/text/foo2.txt');
        $this->assertFileExists(self::$tempDir.'/text/foo2.txt');
        $this->assertEquals($data, file_get_contents(self::$tempDir.'/text/foo2.txt'));
    }

    public function testHasSameHashChecksFileHashes()
    {
        $filesystem = new Filesystem;

        mkdir(self::$tempDir.'/text');
        file_put_contents(self::$tempDir.'/text/foo.txt', 'contents');
        file_put_contents(self::$tempDir.'/text/foo2.txt', 'contents');
        file_put_contents(self::$tempDir.'/text/foo3.txt', 'invalid');

        $this->assertTrue($filesystem->hasSameHash(self::$tempDir.'/text/foo.txt', self::$tempDir.'/text/foo2.txt'));
        $this->assertFalse($filesystem->hasSameHash(self::$tempDir.'/text/foo.txt', self::$tempDir.'/text/foo3.txt'));
        $this->assertFalse($filesystem->hasSameHash(self::$tempDir.'/text/foo4.txt', self::$tempDir.'/text/foo.txt'));
        $this->assertFalse($filesystem->hasSameHash(self::$tempDir.'/text/foo.txt', self::$tempDir.'/text/foo4.txt'));
    }

    public function testIsFileChecksFilesProperly()
    {
        $filesystem = new Filesystem;
        mkdir(self::$tempDir.'/help');
        file_put_contents(self::$tempDir.'/help/foo.txt', 'contents');
        $this->assertTrue($filesystem->isFile(self::$tempDir.'/help/foo.txt'));
        $this->assertFalse($filesystem->isFile(self::$tempDir.'./help'));
    }

    public function testFilesMethodReturnsFileInfoObjects()
    {
        mkdir(self::$tempDir.'/objects');
        file_put_contents(self::$tempDir.'/objects/1.txt', '1');
        file_put_contents(self::$tempDir.'/objects/2.txt', '2');
        mkdir(self::$tempDir.'/objects/bar');
        $files = new Filesystem;
        $this->assertContainsOnlyInstancesOf(SplFileInfo::class, $files->files(self::$tempDir.'/objects'));
        unset($files);
    }

    public function testAllFilesReturnsFileInfoObjects()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        file_put_contents(self::$tempDir.'/bar.txt', 'bar');
        $files = new Filesystem;
        $this->assertContainsOnlyInstancesOf(SplFileInfo::class, $files->allFiles(self::$tempDir));
    }

    public function testHashWithDefaultValue()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $filesystem = new Filesystem;
        $this->assertSame('acbd18db4cc2f85cedef654fccc4a4d8', $filesystem->hash(self::$tempDir.'/foo.txt'));
    }

    public function testHash()
    {
        file_put_contents(self::$tempDir.'/foo.txt', 'foo');
        $filesystem = new Filesystem;
        $this->assertSame('0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33', $filesystem->hash(self::$tempDir.'/foo.txt', 'sha1'));
        $this->assertSame('76d3bc41c9f588f7fcd0d5bf4718f8f84b1c41b20882703100b9eb9413807c01', $filesystem->hash(self::$tempDir.'/foo.txt', 'sha3-256'));
    }

    /**
     * @param  string  $file
     * @return int
     */
    private function getFilePermissions($file)
    {
        $filePerms = fileperms($file);
        $filePerms = substr(sprintf('%o', $filePerms), -3);

        return (int) base_convert($filePerms, 8, 10);
    }
}
