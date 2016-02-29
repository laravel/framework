<?php

use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    public function testGetRetrievesFiles()
    {
        file_put_contents(__DIR__.'/file.txt', 'Hello World');
        $files = new Filesystem;
        $this->assertEquals('Hello World', $files->get(__DIR__.'/file.txt'));
        @unlink(__DIR__.'/file.txt');
    }

    public function testPutStoresFiles()
    {
        $files = new Filesystem;
        $files->put(__DIR__.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile(__DIR__.'/file.txt', 'Hello World');
        @unlink(__DIR__.'/file.txt');
    }

    public function testDeleteRemovesFiles()
    {
        file_put_contents(__DIR__.'/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->delete(__DIR__.'/file.txt');
        $this->assertFileNotExists(__DIR__.'/file.txt');
        @unlink(__DIR__.'/file.txt');
    }

    public function testPrependExistingFiles()
    {
        $files = new Filesystem;
        $files->put(__DIR__.'/file.txt', 'World');
        $files->prepend(__DIR__.'/file.txt', 'Hello ');
        $this->assertStringEqualsFile(__DIR__.'/file.txt', 'Hello World');
        @unlink(__DIR__.'/file.txt');
    }

    public function testPrependNewFiles()
    {
        $files = new Filesystem;
        $files->prepend(__DIR__.'/file.txt', 'Hello World');
        $this->assertStringEqualsFile(__DIR__.'/file.txt', 'Hello World');
        @unlink(__DIR__.'/file.txt');
    }

    public function testDeleteDirectory()
    {
        mkdir(__DIR__.'/foo');
        file_put_contents(__DIR__.'/foo/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->deleteDirectory(__DIR__.'/foo');
        $this->assertFalse(is_dir(__DIR__.'/foo'));
        $this->assertFileNotExists(__DIR__.'/foo/file.txt');
    }

    public function testCleanDirectory()
    {
        mkdir(__DIR__.'/foo');
        file_put_contents(__DIR__.'/foo/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->cleanDirectory(__DIR__.'/foo');
        $this->assertTrue(is_dir(__DIR__.'/foo'));
        $this->assertFileNotExists(__DIR__.'/foo/file.txt');
        @rmdir(__DIR__.'/foo');
    }

    public function testMacro()
    {
        file_put_contents(__DIR__.'/foo.txt', 'Hello World');
        $files = new Filesystem;
        $files->macro('getFoo', function () use ($files) { return $files->get(__DIR__.'/foo.txt'); });
        $this->assertEquals('Hello World', $files->getFoo());
        @unlink(__DIR__.'/foo.txt');
    }

    public function testFilesMethod()
    {
        mkdir(__DIR__.'/foo');
        file_put_contents(__DIR__.'/foo/1.txt', '1');
        file_put_contents(__DIR__.'/foo/2.txt', '2');
        mkdir(__DIR__.'/foo/bar');
        $files = new Filesystem;
        $this->assertEquals([__DIR__.'/foo/1.txt', __DIR__.'/foo/2.txt'], $files->files(__DIR__.'/foo'));
        unset($files);
        @unlink(__DIR__.'/foo/1.txt');
        @unlink(__DIR__.'/foo/2.txt');
        @rmdir(__DIR__.'/foo/bar');
        @rmdir(__DIR__.'/foo');
    }

    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $files = new Filesystem;
        $this->assertFalse($files->copyDirectory(__DIR__.'/foo/bar/baz/breeze/boom', __DIR__));
    }

    public function testCopyDirectoryMovesEntireDirectory()
    {
        mkdir(__DIR__.'/tmp', 0777, true);
        file_put_contents(__DIR__.'/tmp/foo.txt', '');
        file_put_contents(__DIR__.'/tmp/bar.txt', '');
        mkdir(__DIR__.'/tmp/nested', 0777, true);
        file_put_contents(__DIR__.'/tmp/nested/baz.txt', '');

        $files = new Filesystem;
        $files->copyDirectory(__DIR__.'/tmp', __DIR__.'/tmp2');
        $this->assertTrue(is_dir(__DIR__.'/tmp2'));
        $this->assertFileExists(__DIR__.'/tmp2/foo.txt');
        $this->assertFileExists(__DIR__.'/tmp2/bar.txt');
        $this->assertTrue(is_dir(__DIR__.'/tmp2/nested'));
        $this->assertFileExists(__DIR__.'/tmp2/nested/baz.txt');

        unlink(__DIR__.'/tmp/nested/baz.txt');
        rmdir(__DIR__.'/tmp/nested');
        unlink(__DIR__.'/tmp/bar.txt');
        unlink(__DIR__.'/tmp/foo.txt');
        rmdir(__DIR__.'/tmp');

        unlink(__DIR__.'/tmp2/nested/baz.txt');
        rmdir(__DIR__.'/tmp2/nested');
        unlink(__DIR__.'/tmp2/foo.txt');
        unlink(__DIR__.'/tmp2/bar.txt');
        rmdir(__DIR__.'/tmp2');
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem;
        $files->get(__DIR__.'/unknown-file.txt');
    }

    public function testGetRequireReturnsProperly()
    {
        file_put_contents(__DIR__.'/file.php', '<?php return "Howdy?"; ?>');
        $files = new Filesystem;
        $this->assertEquals('Howdy?', $files->getRequire(__DIR__.'/file.php'));
        @unlink(__DIR__.'/file.php');
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRequireThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem;
        $files->getRequire(__DIR__.'/file.php');
    }

    public function testAppendAddsDataToFile()
    {
        file_put_contents(__DIR__.'/file.txt', 'foo');
        $files = new Filesystem;
        $bytesWritten = $files->append(__DIR__.'/file.txt', 'bar');
        $this->assertEquals(mb_strlen('bar', '8bit'), $bytesWritten);
        $this->assertFileExists(__DIR__.'/file.txt');
        $this->assertStringEqualsFile(__DIR__.'/file.txt', 'foobar');
        @unlink(__DIR__.'/file.txt');
    }

    public function testMoveMovesFiles()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $files->move(__DIR__.'/foo.txt', __DIR__.'/bar.txt');
        $this->assertFileExists(__DIR__.'/bar.txt');
        $this->assertFileNotExists(__DIR__.'/foo.txt');
        @unlink(__DIR__.'/bar.txt');
    }

    public function testExtensionReturnsExtension()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals('txt', $files->extension(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }
    
    public function testBasenameReturnsBasename()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals('foo.txt', $files->basename(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }
    
    public function testDirnameReturnsDirectory()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals(__DIR__, $files->dirname(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }

    public function testTypeIndentifiesFile()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals('file', $files->type(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }

    public function testTypeIndentifiesDirectory()
    {
        mkdir(__DIR__.'/foo');
        $files = new Filesystem;
        $this->assertEquals('dir', $files->type(__DIR__.'/foo'));
        @rmdir(__DIR__.'/foo');
    }

    public function testSizeOutputsSize()
    {
        $size = file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals($size, $files->size(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }

    /**
     * @requires extension fileinfo
     */
    public function testMimeTypeOutputsMimeType()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        $this->assertEquals('text/plain', $files->mimeType(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }

    public function testIsWritable()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        $files = new Filesystem;
        @chmod(__DIR__.'/foo.txt', 0444);
        $this->assertFalse($files->isWritable(__DIR__.'/foo.txt'));
        @chmod(__DIR__.'/foo.txt', 0777);
        $this->assertTrue($files->isWritable(__DIR__.'/foo.txt'));
        @unlink(__DIR__.'/foo.txt');
    }

    public function testGlobFindsFiles()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        file_put_contents(__DIR__.'/bar.txt', 'bar');
        $files = new Filesystem;
        $glob = $files->glob(__DIR__.'/*.txt');
        $this->assertContains(__DIR__.'/foo.txt', $glob);
        $this->assertContains(__DIR__.'/bar.txt', $glob);
        @unlink(__DIR__.'/foo.txt');
        @unlink(__DIR__.'/bar.txt');
    }

    public function testAllFilesFindsFiles()
    {
        file_put_contents(__DIR__.'/foo.txt', 'foo');
        file_put_contents(__DIR__.'/bar.txt', 'bar');
        $files = new Filesystem;
        $allFiles = [];
        foreach ($files->allFiles(__DIR__) as $file) {
            $allFiles[] = $file->getFilename();
        }
        $this->assertContains('foo.txt', $allFiles);
        $this->assertContains('bar.txt', $allFiles);
        @unlink(__DIR__.'/foo.txt');
        @unlink(__DIR__.'/bar.txt');
    }

    public function testDirectoriesFindsDirectories()
    {
        mkdir(__DIR__.'/foo');
        mkdir(__DIR__.'/bar');
        $files = new Filesystem;
        $directories = $files->directories(__DIR__);
        $this->assertContains(__DIR__.DIRECTORY_SEPARATOR.'foo', $directories);
        $this->assertContains(__DIR__.DIRECTORY_SEPARATOR.'bar', $directories);
        @rmdir(__DIR__.'/foo');
        @rmdir(__DIR__.'/bar');
    }

    public function testMakeDirectory()
    {
        $files = new Filesystem;
        $this->assertTrue($files->makeDirectory(__DIR__.'/foo'));
        $this->assertFileExists(__DIR__.'/foo');
        @rmdir(__DIR__.'/foo');
    }
}
