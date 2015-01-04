<?php

use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase {

	static protected $testDirectory = __DIR__;

	protected $filesystem = null;

	public function tearDown()
	{
		if($this->filesystem == null){
			$this->filesystem = new Filesystem();
		}
		$tempFiles = ['foo','bar','foo.txt','bar.txt','file.txt','file.php','tmp','tmp2'];
		foreach ($tempFiles as $item) {
			if($this->filesystem->isDirectory(self::$testDirectory.DIRECTORY_SEPARATOR.$item)){
				$this->filesystem->deleteDirectory(self::$testDirectory.DIRECTORY_SEPARATOR.$item);
			}
			else if($this->filesystem->isFile(self::$testDirectory.DIRECTORY_SEPARATOR.$item)){
				$this->filesystem->delete(self::$testDirectory.DIRECTORY_SEPARATOR.$item);
			}
		}
	}

	public function testGetRetrievesFiles()
	{
		file_put_contents(self::$testDirectory.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$this->assertEquals('Hello World', $files->get(self::$testDirectory.'/file.txt'));
		@unlink(self::$testDirectory.'/file.txt');
	}


	/**
	 * @expectedException Illuminate\Filesystem\FileNotFoundException
	 */
	public function testGetThrowsExceptionNonexisitingFile()
	{
		$files = new Filesystem;
		$files->get(self::$testDirectory.'/unknown-file.txt');
	}


	public function testGetRequireReturnsProperly()
	{
		file_put_contents(self::$testDirectory.'/file.php', '<?php return "Howdy?"; ?>');
		$files = new Filesystem;
		$this->assertEquals('Howdy?',$files->getRequire(self::$testDirectory.'/file.php'));
		@unlink(self::$testDirectory.'/file.php');
	}


	/**
	 * @expectedException Illuminate\Filesystem\FileNotFoundException
	 */
	public function testGetRequireThrowsExceptionNonexisitingFile()
	{
		$files = new Filesystem;
		$files->getRequire(self::$testDirectory.'/file.php');
	}


	public function testAppendAddsDataToFile()
	{
		file_put_contents(self::$testDirectory.'/file.txt', 'foo');
		$files = new Filesystem;
		$bytesWritten = $files->append(self::$testDirectory.'/file.txt','bar');
		$this->assertEquals(mb_strlen('bar','8bit'),$bytesWritten);
		$this->assertFileExists(self::$testDirectory.'/file.txt');
		$this->assertStringEqualsFile(self::$testDirectory.'/file.txt','foobar');
		@unlink(self::$testDirectory.'/file.txt');
	}


	public function testMoveMovesFiles()
	{
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$files->move(self::$testDirectory.'/foo.txt',self::$testDirectory.'/bar.txt');
		$this->assertFileExists(self::$testDirectory.'/bar.txt');
		$this->assertFileNotExists(self::$testDirectory.'/foo.txt');
		@unlink(self::$testDirectory.'/bar.txt');
	}


	public function testExtensionReturnsExtension()
	{
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals('txt',$files->extension(self::$testDirectory.'/foo.txt'));
		@unlink(self::$testDirectory.'/foo.txt');
	}


	public function testTypeIndentifiesFile()
	{
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals('file',$files->type(self::$testDirectory.'/foo.txt'));
		@unlink(self::$testDirectory.'/foo.txt');
	}


	public function testTypeIndentifiesDirectory()
	{
		mkdir(self::$testDirectory.'/foo');
		$files = new Filesystem;
		$this->assertEquals('dir',$files->type(self::$testDirectory.'/foo'));
		@rmdir(self::$testDirectory.'/foo');
	}


	public function testSizeOutputsSize()
	{
		$size = file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals($size,$files->size(self::$testDirectory.'/foo.txt'));
		@unlink(self::$testDirectory.'/foo.txt');
	}


	public function testLastModified()
	{
		$time = time();
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals($time,$files->lastModified(self::$testDirectory.'/foo.txt'));
		@unlink(self::$testDirectory.'/foo.txt');
	}


	public function testIsWritable()
	{
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		@chmod(self::$testDirectory.'/foo.txt', 0444);
		$this->assertFalse($files->isWritable(self::$testDirectory.'/foo.txt'));
		@chmod(self::$testDirectory.'/foo.txt', 0777);
		$this->assertTrue($files->isWritable(self::$testDirectory.'/foo.txt'));
		@unlink(self::$testDirectory.'/foo.txt');
	}


	public function testGlobFindsFiles()
	{
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		file_put_contents(self::$testDirectory.'/bar.txt', 'bar');
		$files = new Filesystem;
		$glob = $files->glob(self::$testDirectory.'/*.txt');
		$this->assertContains(self::$testDirectory.'/foo.txt',$glob);
		$this->assertContains(self::$testDirectory.'/bar.txt',$glob);
		@unlink(self::$testDirectory.'/foo.txt');
		@unlink(self::$testDirectory.'/bar.txt');
	}


	public function testAllFilesFindsFiles()
	{
		file_put_contents(self::$testDirectory.'/foo.txt', 'foo');
		file_put_contents(self::$testDirectory.'/bar.txt', 'bar');
		$files = new Filesystem;
		$allFiles = [];
		foreach($files->allFiles(self::$testDirectory) as $file)
			$allFiles[] = $file->getFilename();
		$this->assertContains('foo.txt',$allFiles);
		$this->assertContains('bar.txt',$allFiles);
		@unlink(self::$testDirectory.'/foo.txt');
		@unlink(self::$testDirectory.'/bar.txt');
	}


	public function testDirectoriesFindsDirectories()
	{
		mkdir(self::$testDirectory.'/foo');
		mkdir(self::$testDirectory.'/bar');
		$files = new Filesystem;
		$directories = $files->directories(self::$testDirectory);
		$this->assertContains(self::$testDirectory.DIRECTORY_SEPARATOR.'foo',$directories);
		$this->assertContains(self::$testDirectory.DIRECTORY_SEPARATOR.'bar',$directories);
		@rmdir(self::$testDirectory.'/foo');
		@rmdir(self::$testDirectory.'/bar');
	}

	public function testMakeDirectory()
	{
		$files = new Filesystem;
		$this->assertTrue($files->makeDirectory(self::$testDirectory.'/foo'));
		$this->assertFileExists(self::$testDirectory.'/foo');
		@rmdir(self::$testDirectory.'/foo');
	}


	public function testPutStoresFiles()
	{
		$files = new Filesystem;
		$files->put(self::$testDirectory.'/file.txt', 'Hello World');
		$this->assertEquals('Hello World', file_get_contents(self::$testDirectory.'/file.txt'));
		@unlink(self::$testDirectory.'/file.txt');
	}


	public function testDeleteRemovesFiles()
	{
		file_put_contents(self::$testDirectory.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->delete(self::$testDirectory.'/file.txt');
		$this->assertFileNotExists(self::$testDirectory.'/file.txt');
		@unlink(self::$testDirectory.'/file.txt');
	}


	public function testPrependExistingFiles()
	{
		$files = new Filesystem;
		$files->put(self::$testDirectory.'/file.txt', 'World');
		$files->prepend(self::$testDirectory.'/file.txt', 'Hello ');
		$this->assertEquals('Hello World', file_get_contents(self::$testDirectory.'/file.txt'));
		@unlink(self::$testDirectory.'/file.txt');
	}


	public function testPrependNewFiles()
	{
		$files = new Filesystem;
		$files->prepend(self::$testDirectory.'/file.txt', 'Hello World');
		$this->assertEquals('Hello World', file_get_contents(self::$testDirectory.'/file.txt'));
		@unlink(self::$testDirectory.'/file.txt');
	}


	public function testDeleteDirectory()
	{
		mkdir(self::$testDirectory.'/foo');
		file_put_contents(self::$testDirectory.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->deleteDirectory(self::$testDirectory.'/foo');
		$this->assertFalse(is_dir(self::$testDirectory.'/foo'));
		$this->assertFileNotExists(self::$testDirectory.'/foo/file.txt');
	}


	public function testDeleteDirectoryWorksRecursively()
	{
		mkdir(self::$testDirectory.'/foo');
		mkdir(self::$testDirectory.'/foo/bar');
		$files = new Filesystem;
		$files->deleteDirectory(self::$testDirectory.'/foo');
		$this->assertFalse(is_dir(self::$testDirectory.'/foo/bar'));
		$this->assertFalse(is_dir(self::$testDirectory.'/foo'));
	}


	public function testCleanDirectory()
	{
		mkdir(self::$testDirectory.'/foo');
		file_put_contents(self::$testDirectory.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->cleanDirectory(self::$testDirectory.'/foo');
		$this->assertTrue(is_dir(self::$testDirectory.'/foo'));
		$this->assertFileNotExists(self::$testDirectory.'/foo/file.txt');
		@rmdir(self::$testDirectory.'/foo');
	}


	public function testFilesMethod()
	{
		mkdir(self::$testDirectory.'/foo');
		file_put_contents(self::$testDirectory.'/foo/1.txt', '1');
		file_put_contents(self::$testDirectory.'/foo/2.txt', '2');
		mkdir(self::$testDirectory.'/foo/bar');
		$files = new Filesystem;
		$this->assertEquals(array(self::$testDirectory.'/foo/1.txt', self::$testDirectory.'/foo/2.txt'), $files->files(self::$testDirectory.'/foo'));
		unset($files);
		@unlink(self::$testDirectory.'/foo/1.txt');
		@unlink(self::$testDirectory.'/foo/2.txt');
		@rmdir(self::$testDirectory.'/foo/bar');
		@rmdir(self::$testDirectory.'/foo');
	}


	public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
	{
		$files = new Filesystem;
		$this->assertFalse($files->copyDirectory(self::$testDirectory.'/foo/bar/baz/breeze/boom', self::$testDirectory));
	}


	public function testCopyDirectoryMovesEntireDirectory()
	{
		mkdir(self::$testDirectory.'/tmp', 0777, true);
		file_put_contents(self::$testDirectory.'/tmp/foo.txt', '');
		file_put_contents(self::$testDirectory.'/tmp/bar.txt', '');
		mkdir(self::$testDirectory.'/tmp/nested', 0777, true);
		file_put_contents(self::$testDirectory.'/tmp/nested/baz.txt', '');

		$files = new Filesystem;
		$files->copyDirectory(self::$testDirectory.'/tmp', self::$testDirectory.'/tmp2');
		$this->assertTrue(is_dir(self::$testDirectory.'/tmp2'));
		$this->assertFileExists(self::$testDirectory.'/tmp2/foo.txt');
		$this->assertFileExists(self::$testDirectory.'/tmp2/bar.txt');
		$this->assertTrue(is_dir(self::$testDirectory.'/tmp2/nested'));
		$this->assertFileExists(self::$testDirectory.'/tmp2/nested/baz.txt');

		unlink(self::$testDirectory.'/tmp/nested/baz.txt');
		rmdir(self::$testDirectory.'/tmp/nested');
		unlink(self::$testDirectory.'/tmp/bar.txt');
		unlink(self::$testDirectory.'/tmp/foo.txt');
		rmdir(self::$testDirectory.'/tmp');

		unlink(self::$testDirectory.'/tmp2/nested/baz.txt');
		rmdir(self::$testDirectory.'/tmp2/nested');
		unlink(self::$testDirectory.'/tmp2/foo.txt');
		unlink(self::$testDirectory.'/tmp2/bar.txt');
		rmdir(self::$testDirectory.'/tmp2');
	}

}
