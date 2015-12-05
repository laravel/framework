<?php

use org\bovigo\vfs\vfsStream;
use Illuminate\Filesystem\Filesystem;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\content\LargeFileContent;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    /**
     * Setup the environment.
     *
     * @return void
     */
    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    public function testGetRetrievesFiles()
    {
        $file = vfsStream::newFile('temp.txt')->withContent('Foo Bar')->at($this->root);
        $files = new Filesystem;
        $this->assertEquals('Foo Bar', $files->get($file->url()));
    }

    public function testPutStoresFiles()
    {
        $file = vfsStream::newFile('temp.txt')->at($this->root);
        $files = new Filesystem;
        $files->put($file->url(), 'Hello World');
        $this->assertStringEqualsFile($file->url(), 'Hello World');
    }

    public function testPrependExistingFiles()
    {
        $file = vfsStream::newFile('temp.txt')->withContent('Hello World')->at($this->root);
        $files = new Filesystem;
        $files->prepend($file->url(), 'Laravel, ');
        $this->assertStringEqualsFile($file->url(), 'Laravel, Hello World');
    }

    public function testPrependNewFiles()
    {
        $url = sprintf('%s\%s', $this->root->url(), 'newTempFile.txt');
        $files = new Filesystem;
        $files->prepend($url, 'Hello World!');
        $this->assertStringEqualsFile($url, 'Hello World!');
    }

    public function testDeleteDirectory()
    {
        $this->root->addChild(new vfsStreamDirectory('temp'));
        $dir = $this->root->getChild('temp');
        $file = vfsStream::newFile('bar.txt')->withContent('bar')->at($dir);

        $files = new Filesystem;
        $this->assertTrue(is_dir($dir->url()));
        $files->deleteDirectory($dir->url());
        $this->assertFalse(is_dir($dir->url()));
        $this->assertFileNotExists($file->url());
    }

    public function testCleanDirectory()
    {
        $this->root->addChild(new vfsStreamDirectory('party'));
        $dir = $this->root->getChild('party');
        $file = vfsStream::newFile('soda.txt')->withContent('party')->at($dir);

        $files = new Filesystem;
        $files->cleanDirectory($dir->url());
        $this->assertTrue(is_dir($dir->url()));
        $this->assertFileNotExists($file->url());
    }

    public function testDeleteRemovesFiles()
    {
        $file = vfsStream::newFile('unlucky.txt')->withContent('So sad')->at($this->root);
        $files = new Filesystem;
        $this->assertTrue($files->exists($file->url()));
        $files->delete($file->url());
        $this->assertFalse($files->exists($file->url()));
    }

    public function testMacro()
    {
        $file = vfsStream::newFile('name.txt')->withContent('Taylor')->at($this->root);
        $files = new Filesystem;
        $files->macro('getName', function () use ($files, $file) { return $files->get($file->url()); });
        $this->assertEquals('Taylor', $files->getName());
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
        $this->assertFalse($files->copyDirectory(vfsStream::url('foo/bar/baz/tmp'), vfsStream::url('foo')));
    }

    public function testCopyDirectoryMovesEntireDirectory()
    {
        $this->root->addChild(new vfsStreamDirectory('tmp', 0777));
        $dir = $this->root->getChild('tmp');
        vfsStream::newFile('foo.txt')->withContent('foo')->at($dir);
        vfsStream::newFile('bar.txt')->withContent('bar')->at($dir);

        $dir->addChild(new vfsStreamDirectory('nested', 0777));
        vfsStream::newFile('baz.txt')->withContent('baz')->at($dir->getChild('nested'));

        $files = new Filesystem;
        $tmp2 = sprintf('%s/%s', $this->root->url(), 'tmp2');
        $files->copyDirectory($dir->url(), $tmp2);
        $this->assertTrue(is_dir($tmp2));
        $this->assertFileExists($tmp2.'/foo.txt');
        $this->assertFileExists($tmp2.'/bar.txt');
        $this->assertTrue(is_dir($tmp2.'/nested'));
        $this->assertFileExists($tmp2.'/nested/baz.txt');
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem;
        $files->get(vfsStream::url('foo/bar/baz/tmp/file.txt'));
    }

    public function testGetRequireReturnsProperly()
    {
        $file = vfsStream::newFile('file.php')->withContent('<?php return "Howdy?"; ?>')->at($this->root);
        $files = new Filesystem;
        $this->assertEquals('Howdy?', $files->getRequire($file->url()));
    }

    /**
     * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRequireThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem;
        $files->getRequire(vfsStream::url('foo/bar/tmp/file.php'));
    }

    public function testAppendAddsDataToFile()
    {
        $file = vfsStream::newFile('foo.txt')->withContent('foo')->at($this->root);
        $files = new Filesystem;
        $bytesWritten = $files->append($file->url(), 'bar');
        $this->assertEquals(mb_strlen('bar', '8bit'), $bytesWritten);
        $this->assertFileExists($file->url());
        $this->assertStringEqualsFile($file->url(), 'foobar');
    }

    public function testMoveMovesFiles()
    {
        $file = vfsStream::newFile('pop.txt')->withContent('pop')->at($this->root);
        $rock = $this->root->url().'/rock.txt';
        $files = new Filesystem;
        $files->move($file->url(), $rock);
        $this->assertFileExists($rock);
        $this->assertStringEqualsFile($rock, 'pop');
        $this->assertFileNotExists($this->root->url().'/pop.txt');
    }

    public function testExtensionReturnsExtension()
    {
        $file = vfsStream::newFile('rock.csv')->withContent('pop,rock')->at($this->root);
        $files = new Filesystem;
        $this->assertEquals('csv', $files->extension($file->url()));
    }

    public function testTypeIndentifiesFile()
    {
        $file = vfsStream::newFile('rock.csv')->withContent('pop,rock')->at($this->root);
        $files = new Filesystem;
        $this->assertEquals('file', $files->type($file->url()));
    }

    public function testTypeIndentifiesDirectory()
    {
        $this->root->addChild(new vfsStreamDirectory('music'));
        $dir = $this->root->getChild('music');
        $files = new Filesystem;
        $this->assertEquals('dir', $files->type($dir->url()));
    }

    public function testSizeOutputsSize()
    {
        $content = LargeFileContent::withKilobytes(2);
        $file = vfsStream::newFile('2kb.txt')->withContent($content)->at($this->root);
        $files = new Filesystem;
        $this->assertEquals($file->size(), $files->size($file->url()));
    }

    /**
     * @requires extension fileinfo
     */
    public function testMimeTypeOutputsMimeType()
    {
        $file = vfsStream::newFile('foo.txt')->withContent('foo')->at($this->root);
        $files = new Filesystem;
        $this->assertEquals('text/plain', $files->mimeType($file->url()));
    }

    public function testIsWritable()
    {
        $file = vfsStream::newFile('foo.txt', 0444)->withContent('foo')->at($this->root);
        $files = new Filesystem;
        $this->assertFalse($files->isWritable($file->url()));
        $file->chmod(0777);
        $this->assertTrue($files->isWritable($file->url()));
    }

    public function testIsFile()
    {
        $this->root->addChild(new vfsStreamDirectory('assets'));
        $dir = $this->root->getChild('assets');
        $file = vfsStream::newFile('foo.txt')->withContent('foo')->at($this->root);
        $files = new Filesystem;
        $this->assertFalse($files->isFile($dir->url()));
        $this->assertTrue($files->isFile($file->url()));
    }

    public function testIsDirectory()
    {
        $this->root->addChild(new vfsStreamDirectory('assets'));
        $dir = $this->root->getChild('assets');
        $file = vfsStream::newFile('foo.txt')->withContent('foo')->at($this->root);
        $files = new Filesystem;
        $this->assertTrue($files->isDirectory($dir->url()));
        $this->assertFalse($files->isDirectory($file->url()));
    }

    public function testLastModified()
    {
        $file = vfsStream::newFile('foo.txt')->withContent('foo')->at($this->root);
        $files = new Filesystem;
        $this->assertEquals($file->filemtime(), $files->lastModified($file->url()));
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
        $this->root->addChild(new vfsStreamDirectory('languages'));
        $dir = $this->root->getChild('languages');
        $file1 = vfsStream::newFile('php.txt')->withContent('PHP')->at($dir);
        $file2 = vfsStream::newFile('c.txt')->withContent('C')->at($dir);
        $files = new Filesystem;
        $allFiles = [];
        foreach ($files->allFiles($dir->url()) as $file) {
            $allFiles[] = $file->getFilename();
        }
        $this->assertContains($file1->getName(), $allFiles);
        $this->assertContains($file2->getName(), $allFiles);
    }

    public function testDirectoriesFindsDirectories()
    {
        $this->root->addChild(new vfsStreamDirectory('languages'));
        $this->root->addChild(new vfsStreamDirectory('music'));
        $dir1 = $this->root->getChild('languages');
        $dir2 = $this->root->getChild('music');
        $files = new Filesystem;
        $directories = $files->directories($this->root->url());
        $this->assertContains($dir1->url(), $directories);
        $this->assertContains($dir2->url(), $directories);
    }

    public function testMakeDirectory()
    {
        $files = new Filesystem;
        $dir = $this->root->url().'/laravel';
        $this->assertTrue($files->makeDirectory($dir));
        $this->assertTrue($files->exists($dir));
    }
}
