<?php

namespace Illuminate\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends TestCase
{
    protected $files;
    private $tempDir;

    public function setUp()
    {
        $this->files = new Filesystem;
        $this->tempDir = __DIR__.'/tmp';
        mkdir($this->tempDir);
    }

    public function tearDown()
    {
        $this->files->deleteDirectory($this->tempDir);
    }

    public function testGetRetrievesFiles()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello World');
        $this->assertEquals('Hello World', $this->files->get($this->tempDir.'/file.txt'));
    }

    public function testPutStoresFiles()
    {
        $this->files->put($this->tempDir.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello World');
    }

    public function testSetChmod()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello World');
        $this->files->chmod($this->tempDir.'/file.txt', 0755);
        $filePermission = substr(sprintf('%o', fileperms($this->tempDir.'/file.txt')), -4);
        $this->assertEquals('0755', $filePermission);
    }

    public function testGetChmod()
    {
        file_put_contents($this->tempDir.'/file.txt', 'Hello World');
        chmod($this->tempDir.'/file.txt', 0755);
        $filePermission = $this->files->chmod($this->tempDir.'/file.txt');
        $this->assertEquals('0755', $filePermission);
    }

    public function testDeleteRemovesFiles()
    {
        file_put_contents($this->tempDir.'/file1.txt', 'Hello World');
        file_put_contents($this->tempDir.'/file2.txt', 'Hello World');
        file_put_contents($this->tempDir.'/file3.txt', 'Hello World');

        $this->files->delete($this->tempDir.'/file1.txt');
        $this->assertFileNotExists($this->tempDir.'/file1.txt');

        $this->files->delete([$this->tempDir.'/file2.txt', $this->tempDir.'/file3.txt']);
        $this->assertFileNotExists($this->tempDir.'/file2.txt');
        $this->assertFileNotExists($this->tempDir.'/file3.txt');
    }

    public function testPrependExistingFiles()
    {
        $this->files->put($this->tempDir.'/file.txt', 'World');
        $this->files->prepend($this->tempDir.'/file.txt', 'Hello ');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello World');
    }

    public function testPrependNewFiles()
    {
        $this->files->prepend($this->tempDir.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'Hello World');
    }

    public function testDeleteDirectory()
    {
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/file.txt', 'Hello World');
        $this->files->deleteDirectory($this->tempDir.'/foo');
        $this->assertFalse(is_dir($this->tempDir.'/foo'));
        $this->assertFileNotExists($this->tempDir.'/foo/file.txt');
    }

    public function testCleanDirectory()
    {
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/file.txt', 'Hello World');
        $this->files->cleanDirectory($this->tempDir.'/foo');
        $this->assertTrue(is_dir($this->tempDir.'/foo'));
        $this->assertFileNotExists($this->tempDir.'/foo/file.txt');
    }

    public function testMacro()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'Hello World');
        $tempDir = $this->tempDir;
        $files = $this->files;
        $this->files->macro('getFoo', function () use ($files, $tempDir) {
            return $files->get($tempDir.'/foo.txt');
        });
        $this->assertEquals('Hello World', $this->files->getFoo());
    }

    public function testFilesMethod()
    {
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/1.txt', '1');
        file_put_contents($this->tempDir.'/foo/2.txt', '2');
        mkdir($this->tempDir.'/foo/bar');
        $this->assertEquals([$this->tempDir.'/foo/1.txt', $this->tempDir.'/foo/2.txt'], $this->files->files($this->tempDir.'/foo'));
        unset($files);
    }

    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $this->assertFalse($this->files->copyDirectory($this->tempDir.'/foo/bar/baz/breeze/boom', $this->tempDir));
    }

    public function testCopyDirectoryMovesEntireDirectory()
    {
        mkdir($this->tempDir.'/tmp', 0777, true);
        file_put_contents($this->tempDir.'/tmp/foo.txt', '');
        file_put_contents($this->tempDir.'/tmp/bar.txt', '');
        mkdir($this->tempDir.'/tmp/nested', 0777, true);
        file_put_contents($this->tempDir.'/tmp/nested/baz.txt', '');

        $this->files->copyDirectory($this->tempDir.'/tmp', $this->tempDir.'/tmp2');
        $this->assertTrue(is_dir($this->tempDir.'/tmp2'));
        $this->assertFileExists($this->tempDir.'/tmp2/foo.txt');
        $this->assertFileExists($this->tempDir.'/tmp2/bar.txt');
        $this->assertTrue(is_dir($this->tempDir.'/tmp2/nested'));
        $this->assertFileExists($this->tempDir.'/tmp2/nested/baz.txt');
    }

    public function testMoveDirectoryMovesEntireDirectory()
    {
        mkdir($this->tempDir.'/tmp', 0777, true);
        file_put_contents($this->tempDir.'/tmp/foo.txt', '');
        file_put_contents($this->tempDir.'/tmp/bar.txt', '');
        mkdir($this->tempDir.'/tmp/nested', 0777, true);
        file_put_contents($this->tempDir.'/tmp/nested/baz.txt', '');

        $this->files->moveDirectory($this->tempDir.'/tmp', $this->tempDir.'/tmp2');
        $this->assertTrue(is_dir($this->tempDir.'/tmp2'));
        $this->assertFileExists($this->tempDir.'/tmp2/foo.txt');
        $this->assertFileExists($this->tempDir.'/tmp2/bar.txt');
        $this->assertTrue(is_dir($this->tempDir.'/tmp2/nested'));
        $this->assertFileExists($this->tempDir.'/tmp2/nested/baz.txt');
        $this->assertFalse(is_dir($this->tempDir.'/tmp'));
    }

    public function testMoveDirectoryMovesEntireDirectoryAndOverwrites()
    {
        mkdir($this->tempDir.'/tmp', 0777, true);
        file_put_contents($this->tempDir.'/tmp/foo.txt', '');
        file_put_contents($this->tempDir.'/tmp/bar.txt', '');
        mkdir($this->tempDir.'/tmp/nested', 0777, true);
        file_put_contents($this->tempDir.'/tmp/nested/baz.txt', '');
        mkdir($this->tempDir.'/tmp2', 0777, true);
        file_put_contents($this->tempDir.'/tmp2/foo2.txt', '');
        file_put_contents($this->tempDir.'/tmp2/bar2.txt', '');

        $this->files->moveDirectory($this->tempDir.'/tmp', $this->tempDir.'/tmp2', true);
        $this->assertTrue(is_dir($this->tempDir.'/tmp2'));
        $this->assertFileExists($this->tempDir.'/tmp2/foo.txt');
        $this->assertFileExists($this->tempDir.'/tmp2/bar.txt');
        $this->assertTrue(is_dir($this->tempDir.'/tmp2/nested'));
        $this->assertFileExists($this->tempDir.'/tmp2/nested/baz.txt');
        $this->assertFileNotExists($this->tempDir.'/tmp2/foo2.txt');
        $this->assertFileNotExists($this->tempDir.'/tmp2/bar2.txt');
        $this->assertFalse(is_dir($this->tempDir.'/tmp'));
    }

    /**
     * @expectedException \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetThrowsExceptionNonexisitingFile()
    {
        $this->files->get($this->tempDir.'/unknown-file.txt');
    }

    public function testGetRequireReturnsProperly()
    {
        file_put_contents($this->tempDir.'/file.php', '<?php return "Howdy?"; ?>');
        $this->assertEquals('Howdy?', $this->files->getRequire($this->tempDir.'/file.php'));
    }

    /**
     * @expectedException \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRequireThrowsExceptionNonexisitingFile()
    {
        $this->files->getRequire($this->tempDir.'/file.php');
    }

    public function testAppendAddsDataToFile()
    {
        file_put_contents($this->tempDir.'/file.txt', 'foo');
        $bytesWritten = $this->files->append($this->tempDir.'/file.txt', 'bar');
        $this->assertEquals(mb_strlen('bar', '8bit'), $bytesWritten);
        $this->assertFileExists($this->tempDir.'/file.txt');
        $this->assertStringEqualsFile($this->tempDir.'/file.txt', 'foobar');
    }

    public function testMoveMovesFiles()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->files->move($this->tempDir.'/foo.txt', $this->tempDir.'/bar.txt');
        $this->assertFileExists($this->tempDir.'/bar.txt');
        $this->assertFileNotExists($this->tempDir.'/foo.txt');
    }

    public function testExtensionReturnsExtension()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->assertEquals('txt', $this->files->extension($this->tempDir.'/foo.txt'));
    }

    public function testBasenameReturnsBasename()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->assertEquals('foo.txt', $this->files->basename($this->tempDir.'/foo.txt'));
    }

    public function testDirnameReturnsDirectory()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->assertEquals($this->tempDir, $this->files->dirname($this->tempDir.'/foo.txt'));
    }

    public function testTypeIndentifiesFile()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->assertEquals('file', $this->files->type($this->tempDir.'/foo.txt'));
    }

    public function testTypeIndentifiesDirectory()
    {
        mkdir($this->tempDir.'/foo');
        $this->assertEquals('dir', $this->files->type($this->tempDir.'/foo'));
    }

    public function testSizeOutputsSize()
    {
        $size = file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->assertEquals($size, $this->files->size($this->tempDir.'/foo.txt'));
    }

    /**
     * @requires extension fileinfo
     */
    public function testMimeTypeOutputsMimeType()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        $this->assertEquals('text/plain', $this->files->mimeType($this->tempDir.'/foo.txt'));
    }

    public function testIsWritable()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        @chmod($this->tempDir.'/foo.txt', 0444);
        $this->assertFalse($this->files->isWritable($this->tempDir.'/foo.txt'));
        @chmod($this->tempDir.'/foo.txt', 0777);
        $this->assertTrue($this->files->isWritable($this->tempDir.'/foo.txt'));
    }

    public function testIsReadable()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        // chmod is noneffective on Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->assertTrue($this->files->isReadable($this->tempDir.'/foo.txt'));
        } else {
            @chmod($this->tempDir.'/foo.txt', 0000);
            $this->assertFalse($this->files->isReadable($this->tempDir.'/foo.txt'));
            @chmod($this->tempDir.'/foo.txt', 0777);
            $this->assertTrue($this->files->isReadable($this->tempDir.'/foo.txt'));
        }
        $this->assertFalse($this->files->isReadable($this->tempDir.'/doesnotexist.txt'));
    }

    public function testGlobFindsFiles()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        file_put_contents($this->tempDir.'/bar.txt', 'bar');
        $glob = $this->files->glob($this->tempDir.'/*.txt');
        $this->assertContains($this->tempDir.'/foo.txt', $glob);
        $this->assertContains($this->tempDir.'/bar.txt', $glob);
    }

    public function testAllFilesFindsFiles()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        file_put_contents($this->tempDir.'/bar.txt', 'bar');
        $allFiles = [];
        foreach ($this->files->allFiles($this->tempDir) as $file) {
            $allFiles[] = $file->getFilename();
        }
        $this->assertContains('foo.txt', $allFiles);
        $this->assertContains('bar.txt', $allFiles);
    }

    public function testDirectoriesFindsDirectories()
    {
        mkdir($this->tempDir.'/foo');
        mkdir($this->tempDir.'/bar');
        $directories = $this->files->directories($this->tempDir);
        $this->assertContains($this->tempDir.DIRECTORY_SEPARATOR.'foo', $directories);
        $this->assertContains($this->tempDir.DIRECTORY_SEPARATOR.'bar', $directories);
    }

    public function testMakeDirectory()
    {
        $this->assertTrue($this->files->makeDirectory($this->tempDir.'/foo'));
        $this->assertFileExists($this->tempDir.'/foo');
    }

    /**
     * @requires extension pcntl
     */
    public function testSharedGet()
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Skipping since the pcntl extension is not available');
        }

        $content = str_repeat('123456', 1000000);
        $result = 1;

        for ($i = 1; $i <= 20; ++$i) {
            $pid = pcntl_fork();

            if (! $pid) {
                $this->files->put($this->tempDir.'/file.txt', $content, true);
                $read = $this->files->get($this->tempDir.'/file.txt', true);

                exit(strlen($read) === strlen($content) ? 1 : 0);
            }
        }

        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
            $result *= $status;
        }

        $this->assertTrue($result === 1);
    }

    public function testRequireOnceRequiresFileProperly()
    {
        $filesystem = new Filesystem;
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/foo.php', '<?php function random_function_xyz(){};');
        $filesystem->requireOnce($this->tempDir.'/foo/foo.php');
        file_put_contents($this->tempDir.'/foo/foo.php', '<?php function random_function_xyz_changed(){};');
        $filesystem->requireOnce($this->tempDir.'/foo/foo.php');
        $this->assertTrue(function_exists('random_function_xyz'));
        $this->assertFalse(function_exists('random_function_xyz_changed'));
    }

    public function testCopyCopiesFileProperly()
    {
        $filesystem = new Filesystem;
        $data = 'contents';
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/foo.txt', $data);
        $filesystem->copy($this->tempDir.'/foo/foo.txt', $this->tempDir.'/foo/foo2.txt');
        $this->assertTrue(file_exists($this->tempDir.'/foo/foo2.txt'));
        $this->assertEquals($data, file_get_contents($this->tempDir.'/foo/foo2.txt'));
    }

    public function testIsFileChecksFilesProperly()
    {
        $filesystem = new Filesystem;
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/foo.txt', 'contents');
        $this->assertTrue($filesystem->isFile($this->tempDir.'/foo/foo.txt'));
        $this->assertFalse($filesystem->isFile($this->tempDir.'./foo'));
    }

    public function testFilesMethodReturnsFileInfoObjects()
    {
        mkdir($this->tempDir.'/foo');
        file_put_contents($this->tempDir.'/foo/1.txt', '1');
        file_put_contents($this->tempDir.'/foo/2.txt', '2');
        mkdir($this->tempDir.'/foo/bar');
        foreach ($this->files->files($this->tempDir.'/foo') as $file) {
            $this->assertInstanceOf(\SplFileInfo::class, $file);
        }
        unset($this->files);
    }

    public function testAllFilesReturnsFileInfoObjects()
    {
        file_put_contents($this->tempDir.'/foo.txt', 'foo');
        file_put_contents($this->tempDir.'/bar.txt', 'bar');
        foreach ($this->files->allFiles($this->tempDir) as $file) {
            $this->assertInstanceOf(\SplFileInfo::class, $file);
        }
    }
}
