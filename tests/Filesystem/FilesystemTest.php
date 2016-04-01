<?php

use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    public static $TEMP_DIR = __DIR__.'/tmp';

    public function setUp()
    {
        mkdir(static::$TEMP_DIR);
    }

    public function tearDown()
    {
        $files = new Filesystem();
        $files->deleteDirectory(static::$TEMP_DIR);
    }

    public function testGetRetrievesFiles()
    {
        file_put_contents(static::$TEMP_DIR.'/file.txt', 'Hello World');
        $files = new Filesystem();
        $this->assertEquals('Hello World', $files->get(static::$TEMP_DIR.'/file.txt'));
    }

    public function testPutStoresFiles()
    {
        $files = new Filesystem();
        $files->put(static::$TEMP_DIR.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile(static::$TEMP_DIR.'/file.txt', 'Hello World');
    }

    public function testDeleteRemovesFiles()
    {
        file_put_contents(static::$TEMP_DIR.'/file.txt', 'Hello World');
        $files = new Filesystem();
        $files->delete(static::$TEMP_DIR.'/file.txt');
        $this->assertFileNotExists(static::$TEMP_DIR.'/file.txt');
    }

    public function testPrependExistingFiles()
    {
        $files = new Filesystem();
        $files->put(static::$TEMP_DIR.'/file.txt', 'World');
        $files->prepend(static::$TEMP_DIR.'/file.txt', 'Hello ');
        $this->assertStringEqualsFile(static::$TEMP_DIR.'/file.txt', 'Hello World');
    }

    public function testPrependNewFiles()
    {
        $files = new Filesystem();
        $files->prepend(static::$TEMP_DIR.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile(static::$TEMP_DIR.'/file.txt', 'Hello World');
    }

    public function testDeleteDirectory()
    {
        mkdir(static::$TEMP_DIR.'/foo');
        file_put_contents(static::$TEMP_DIR.'/foo/file.txt', 'Hello World');
        $files = new Filesystem();
        $files->deleteDirectory(static::$TEMP_DIR.'/foo');
        $this->assertFalse(is_dir(static::$TEMP_DIR.'/foo'));
        $this->assertFileNotExists(static::$TEMP_DIR.'/foo/file.txt');
    }

    public function testCleanDirectory()
    {
        mkdir(static::$TEMP_DIR.'/foo');
        file_put_contents(static::$TEMP_DIR.'/foo/file.txt', 'Hello World');
        $files = new Filesystem();
        $files->cleanDirectory(static::$TEMP_DIR.'/foo');
        $this->assertTrue(is_dir(static::$TEMP_DIR.'/foo'));
        $this->assertFileNotExists(static::$TEMP_DIR.'/foo/file.txt');
    }

    public function testMacro()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'Hello World');
        $files = new Filesystem();
        $tempDir = static::$TEMP_DIR;
        $files->macro('getFoo', function () use ($files, $tempDir) { return $files->get($tempDir.'/foo.txt'); });
        $this->assertEquals('Hello World', $files->getFoo());
    }

    public function testFilesMethod()
    {
        mkdir(static::$TEMP_DIR.'/foo');
        file_put_contents(static::$TEMP_DIR.'/foo/1.txt', '1');
        file_put_contents(static::$TEMP_DIR.'/foo/2.txt', '2');
        mkdir(static::$TEMP_DIR.'/foo/bar');
        $files = new Filesystem();
        $this->assertEquals([static::$TEMP_DIR.'/foo/1.txt', static::$TEMP_DIR.'/foo/2.txt'], $files->files(static::$TEMP_DIR.'/foo'));
        unset($files);
    }

    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $files = new Filesystem();
        $this->assertFalse($files->copyDirectory(static::$TEMP_DIR.'/foo/bar/baz/breeze/boom', static::$TEMP_DIR));
    }

    public function testCopyDirectoryMovesEntireDirectory()
    {
        mkdir(static::$TEMP_DIR.'/tmp', 0777, true);
        file_put_contents(static::$TEMP_DIR.'/tmp/foo.txt', '');
        file_put_contents(static::$TEMP_DIR.'/tmp/bar.txt', '');
        mkdir(static::$TEMP_DIR.'/tmp/nested', 0777, true);
        file_put_contents(static::$TEMP_DIR.'/tmp/nested/baz.txt', '');

        $files = new Filesystem();
        $files->copyDirectory(static::$TEMP_DIR.'/tmp', static::$TEMP_DIR.'/tmp2');
        $this->assertTrue(is_dir(static::$TEMP_DIR.'/tmp2'));
        $this->assertFileExists(static::$TEMP_DIR.'/tmp2/foo.txt');
        $this->assertFileExists(static::$TEMP_DIR.'/tmp2/bar.txt');
        $this->assertTrue(is_dir(static::$TEMP_DIR.'/tmp2/nested'));
        $this->assertFileExists(static::$TEMP_DIR.'/tmp2/nested/baz.txt');
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem();
        $files->get(static::$TEMP_DIR.'/unknown-file.txt');
    }

    public function testGetRequireReturnsProperly()
    {
        file_put_contents(static::$TEMP_DIR.'/file.php', '<?php return "Howdy?"; ?>');
        $files = new Filesystem();
        $this->assertEquals('Howdy?', $files->getRequire(static::$TEMP_DIR.'/file.php'));
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRequireThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem();
        $files->getRequire(static::$TEMP_DIR.'/file.php');
    }

    public function testAppendAddsDataToFile()
    {
        file_put_contents(static::$TEMP_DIR.'/file.txt', 'foo');
        $files = new Filesystem();
        $bytesWritten = $files->append(static::$TEMP_DIR.'/file.txt', 'bar');
        $this->assertEquals(mb_strlen('bar', '8bit'), $bytesWritten);
        $this->assertFileExists(static::$TEMP_DIR.'/file.txt');
        $this->assertStringEqualsFile(static::$TEMP_DIR.'/file.txt', 'foobar');
    }

    public function testMoveMovesFiles()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $files->move(static::$TEMP_DIR.'/foo.txt', static::$TEMP_DIR.'/bar.txt');
        $this->assertFileExists(static::$TEMP_DIR.'/bar.txt');
        $this->assertFileNotExists(static::$TEMP_DIR.'/foo.txt');
    }

    public function testExtensionReturnsExtension()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $this->assertEquals('txt', $files->extension(static::$TEMP_DIR.'/foo.txt'));
    }

    public function testBasenameReturnsBasename()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $this->assertEquals('foo.txt', $files->basename(static::$TEMP_DIR.'/foo.txt'));
    }

    public function testDirnameReturnsDirectory()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $this->assertEquals(static::$TEMP_DIR, $files->dirname(static::$TEMP_DIR.'/foo.txt'));
    }

    public function testTypeIndentifiesFile()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $this->assertEquals('file', $files->type(static::$TEMP_DIR.'/foo.txt'));
    }

    public function testTypeIndentifiesDirectory()
    {
        mkdir(static::$TEMP_DIR.'/foo');
        $files = new Filesystem();
        $this->assertEquals('dir', $files->type(static::$TEMP_DIR.'/foo'));
    }

    public function testSizeOutputsSize()
    {
        $size = file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $this->assertEquals($size, $files->size(static::$TEMP_DIR.'/foo.txt'));
    }

    /**
     * @requires extension fileinfo
     */
    public function testMimeTypeOutputsMimeType()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        $this->assertEquals('text/plain', $files->mimeType(static::$TEMP_DIR.'/foo.txt'));
    }

    public function testIsWritable()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        $files = new Filesystem();
        @chmod(static::$TEMP_DIR.'/foo.txt', 0444);
        $this->assertFalse($files->isWritable(static::$TEMP_DIR.'/foo.txt'));
        @chmod(static::$TEMP_DIR.'/foo.txt', 0777);
        $this->assertTrue($files->isWritable(static::$TEMP_DIR.'/foo.txt'));
    }

    public function testGlobFindsFiles()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        file_put_contents(static::$TEMP_DIR.'/bar.txt', 'bar');
        $files = new Filesystem();
        $glob = $files->glob(static::$TEMP_DIR.'/*.txt');
        $this->assertContains(static::$TEMP_DIR.'/foo.txt', $glob);
        $this->assertContains(static::$TEMP_DIR.'/bar.txt', $glob);
    }

    public function testAllFilesFindsFiles()
    {
        file_put_contents(static::$TEMP_DIR.'/foo.txt', 'foo');
        file_put_contents(static::$TEMP_DIR.'/bar.txt', 'bar');
        $files = new Filesystem();
        $allFiles = [];
        foreach ($files->allFiles(static::$TEMP_DIR) as $file) {
            $allFiles[] = $file->getFilename();
        }
        $this->assertContains('foo.txt', $allFiles);
        $this->assertContains('bar.txt', $allFiles);
    }

    public function testDirectoriesFindsDirectories()
    {
        mkdir(static::$TEMP_DIR.'/foo');
        mkdir(static::$TEMP_DIR.'/bar');
        $files = new Filesystem();
        $directories = $files->directories(static::$TEMP_DIR);
        $this->assertContains(static::$TEMP_DIR.DIRECTORY_SEPARATOR.'foo', $directories);
        $this->assertContains(static::$TEMP_DIR.DIRECTORY_SEPARATOR.'bar', $directories);
    }

    public function testMakeDirectory()
    {
        $files = new Filesystem();
        $this->assertTrue($files->makeDirectory(static::$TEMP_DIR.'/foo'));
        $this->assertFileExists(static::$TEMP_DIR.'/foo');
    }

    /**
     * @requires extension pcntl
     */
    public function testSharedGet()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('Skip HHVM test due to bug: https://github.com/facebook/hhvm/issues/5657');

            return;
        }

        $content = '';
        for ($i = 0; $i < 1000000; ++$i) {
            $content .= $i;
        }
        $result = 1;

        for ($i = 1; $i <= 20; ++$i) {
            $pid = pcntl_fork();

            if (! $pid) {
                $files = new Filesystem();
                $files->put(static::$TEMP_DIR.'/file.txt', $content, true);
                $read = $files->get(static::$TEMP_DIR.'/file.txt', true);

                exit(($read === $content) ? 1 : 0);
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
        $filesystem = new Filesystem();
        mkdir(static::$TEMP_DIR.'/foo');
        file_put_contents(static::$TEMP_DIR.'/foo/foo.php', '<?php function random_function_xyz(){};');
        $filesystem->requireOnce(static::$TEMP_DIR.'/foo/foo.php');
        file_put_contents(static::$TEMP_DIR.'/foo/foo.php', '<?php function random_function_xyz_changed(){};');
        $filesystem->requireOnce(static::$TEMP_DIR.'/foo/foo.php');
        $this->assertTrue(function_exists('random_function_xyz'));
        $this->assertFalse(function_exists('random_function_xyz_changed'));
    }

    public function testCopyCopiesFileProperly()
    {
        $filesystem = new Filesystem();
        $data = 'contents';
        mkdir(static::$TEMP_DIR.'/foo');
        file_put_contents(static::$TEMP_DIR.'/foo/foo.txt', $data);
        $filesystem->copy(static::$TEMP_DIR.'/foo/foo.txt', static::$TEMP_DIR.'/foo/foo2.txt');
        $this->assertTrue(file_exists(static::$TEMP_DIR.'/foo/foo2.txt'));
        $this->assertEquals($data, file_get_contents(static::$TEMP_DIR.'/foo/foo2.txt'));
    }

    public function testIsFileChecksFilesProperly()
    {
        $filesystem = new Filesystem();
        mkdir(static::$TEMP_DIR.'/foo');
        file_put_contents(static::$TEMP_DIR.'/foo/foo.txt', 'contents');
        $this->assertTrue($filesystem->isFile(static::$TEMP_DIR.'/foo/foo.txt'));
        $this->assertFalse($filesystem->isFile(static::$TEMP_DIR.'./foo'));
    }
}
