<?php

use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase {

	protected $testDirectory = __DIR__;

	public function testGetRetrievesFiles()
	{
		file_put_contents($this->testDirectory.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$this->assertEquals('Hello World', $files->get($this->testDirectory.'/file.txt'));
		@unlink($this->testDirectory.'/file.txt');
	}


	/**
	 * @expectedException Illuminate\Filesystem\FileNotFoundException
	 */
	public function testGetThrowsExceptionNonexisitingFile()
	{
		$files = new Filesystem;
		$files->get($this->testDirectory.'/unknown-file.txt');
	}


	public function testGetRequireReturnsProperly()
	{
		file_put_contents($this->testDirectory.'/file.php', '<?php return "Howdy?"; ?>');
		$files = new Filesystem;
		$this->assertEquals('Howdy?',$files->getRequire($this->testDirectory.'/file.php'));
		@unlink($this->testDirectory.'/file.php');
	}


	/**
	 * @expectedException Illuminate\Filesystem\FileNotFoundException
	 */
	public function testGetRequireThrowsExceptionNonexisitingFile()
	{
		$files = new Filesystem;
		$files->getRequire($this->testDirectory.'/file.php');
	}


	public function testAppendAddsDataToFile()
	{
		file_put_contents($this->testDirectory.'/file.txt', 'foo');
		$files = new Filesystem;
		$bytesWritten = $files->append($this->testDirectory.'/file.txt','bar');
		$this->assertEquals(mb_strlen('bar','8bit'),$bytesWritten);
		$this->assertFileExists($this->testDirectory.'/file.txt');
		$this->assertStringEqualsFile($this->testDirectory.'/file.txt','foobar');
		@unlink($this->testDirectory.'/file.txt');
	}


	public function testMoveMovesFiles()
	{
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$files->move($this->testDirectory.'/foo.txt',$this->testDirectory.'/bar.txt');
		$this->assertFileExists($this->testDirectory.'/bar.txt');
		$this->assertFileNotExists($this->testDirectory.'/foo.txt');
		@unlink($this->testDirectory.'/bar.txt');
	}


	public function testExtensionReturnsExtension()
	{
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals('txt',$files->extension($this->testDirectory.'/foo.txt'));
		@unlink($this->testDirectory.'/foo.txt');
	}


	public function testTypeIndentifiesFile()
	{
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals('file',$files->type($this->testDirectory.'/foo.txt'));
		@unlink($this->testDirectory.'/foo.txt');
	}


	public function testTypeIndentifiesDirectory()
	{
		mkdir($this->testDirectory.'/foo');
		$files = new Filesystem;
		$this->assertEquals('dir',$files->type($this->testDirectory.'/foo'));
		@rmdir($this->testDirectory.'/foo');
	}


	public function testSizeOutputsSize()
	{
		$size = file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals($size,$files->size($this->testDirectory.'/foo.txt'));
		@unlink($this->testDirectory.'/foo.txt');
	}


	public function testLastModified()
	{
		$time = time();
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals($time,$files->lastModified($this->testDirectory.'/foo.txt'));
		@unlink($this->testDirectory.'/foo.txt');
	}


	public function testIsWritable()
	{
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		$files = new Filesystem;
		@chmod($this->testDirectory.'/foo.txt', 0444);
		$this->assertFalse($files->isWritable($this->testDirectory.'/foo.txt'));
		@chmod($this->testDirectory.'/foo.txt', 0777);
		$this->assertTrue($files->isWritable($this->testDirectory.'/foo.txt'));
		@unlink($this->testDirectory.'/foo.txt');
	}


	public function testGlobFindsFiles()
	{
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		file_put_contents($this->testDirectory.'/bar.txt', 'bar');
		$files = new Filesystem;
		$glob = $files->glob($this->testDirectory.'/*.txt');
		$this->assertContains($this->testDirectory.'/foo.txt',$glob);
		$this->assertContains($this->testDirectory.'/bar.txt',$glob);
		@unlink($this->testDirectory.'/foo.txt');
		@unlink($this->testDirectory.'/bar.txt');
	}


	public function testAllFilesFindsFiles()
	{
		file_put_contents($this->testDirectory.'/foo.txt', 'foo');
		file_put_contents($this->testDirectory.'/bar.txt', 'bar');
		$files = new Filesystem;
		$allFiles = [];
		foreach($files->allFiles($this->testDirectory) as $file)
			$allFiles[] = $file->getFilename();
		$this->assertContains('foo.txt',$allFiles);
		$this->assertContains('bar.txt',$allFiles);
		@unlink($this->testDirectory.'/foo.txt');
		@unlink($this->testDirectory.'/bar.txt');
	}


	public function testDirectoriesFindsDirectories()
	{
		mkdir($this->testDirectory.'/foo');
		mkdir($this->testDirectory.'/bar');
		$files = new Filesystem;
		$directories = $files->directories($this->testDirectory);
		$this->assertContains($this->testDirectory.DIRECTORY_SEPARATOR.'foo',$directories);
		$this->assertContains($this->testDirectory.DIRECTORY_SEPARATOR.'bar',$directories);
		@rmdir($this->testDirectory.'/foo');
		@rmdir($this->testDirectory.'/bar');
	}

	public function testMakeDirectory()
	{
		$files = new Filesystem;
		$this->assertTrue($files->makeDirectory($this->testDirectory.'/foo'));
		$this->assertFileExists($this->testDirectory.'/foo');
		@rmdir($this->testDirectory.'/foo');
	}


	public function testPutStoresFiles()
	{
		$files = new Filesystem;
		$files->put($this->testDirectory.'/file.txt', 'Hello World');
		$this->assertEquals('Hello World', file_get_contents($this->testDirectory.'/file.txt'));
		@unlink($this->testDirectory.'/file.txt');
	}


	public function testDeleteRemovesFiles()
	{
		file_put_contents($this->testDirectory.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->delete($this->testDirectory.'/file.txt');
		$this->assertFileNotExists($this->testDirectory.'/file.txt');
		@unlink($this->testDirectory.'/file.txt');
	}


	public function testPrependExistingFiles()
	{
		$files = new Filesystem;
		$files->put($this->testDirectory.'/file.txt', 'World');
		$files->prepend($this->testDirectory.'/file.txt', 'Hello ');
		$this->assertEquals('Hello World', file_get_contents($this->testDirectory.'/file.txt'));
		@unlink($this->testDirectory.'/file.txt');
	}


	public function testPrependNewFiles()
	{
		$files = new Filesystem;
		$files->prepend($this->testDirectory.'/file.txt', 'Hello World');
		$this->assertEquals('Hello World', file_get_contents($this->testDirectory.'/file.txt'));
		@unlink($this->testDirectory.'/file.txt');
	}


	public function testDeleteDirectory()
	{
		mkdir($this->testDirectory.'/foo');
		file_put_contents($this->testDirectory.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->deleteDirectory($this->testDirectory.'/foo');
		$this->assertFalse(is_dir($this->testDirectory.'/foo'));
		$this->assertFileNotExists($this->testDirectory.'/foo/file.txt');
	}


	public function testDeleteDirectoryWorksRecursively()
	{
		mkdir($this->testDirectory.'/foo');
		mkdir($this->testDirectory.'/foo/bar');
		$files = new Filesystem;
		$files->deleteDirectory($this->testDirectory.'/foo');
		$this->assertFalse(is_dir($this->testDirectory.'/foo/bar'));
		$this->assertFalse(is_dir($this->testDirectory.'/foo'));
	}


	public function testCleanDirectory()
	{
		mkdir($this->testDirectory.'/foo');
		file_put_contents($this->testDirectory.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->cleanDirectory($this->testDirectory.'/foo');
		$this->assertTrue(is_dir($this->testDirectory.'/foo'));
		$this->assertFileNotExists($this->testDirectory.'/foo/file.txt');
		@rmdir($this->testDirectory.'/foo');
	}


	public function testFilesMethod()
	{
		mkdir($this->testDirectory.'/foo');
		file_put_contents($this->testDirectory.'/foo/1.txt', '1');
		file_put_contents($this->testDirectory.'/foo/2.txt', '2');
		mkdir($this->testDirectory.'/foo/bar');
		$files = new Filesystem;
		$this->assertEquals(array($this->testDirectory.'/foo/1.txt', $this->testDirectory.'/foo/2.txt'), $files->files($this->testDirectory.'/foo'));
		unset($files);
		@unlink($this->testDirectory.'/foo/1.txt');
		@unlink($this->testDirectory.'/foo/2.txt');
		@rmdir($this->testDirectory.'/foo/bar');
		@rmdir($this->testDirectory.'/foo');
	}


	public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
	{
		$files = new Filesystem;
		$this->assertFalse($files->copyDirectory($this->testDirectory.'/foo/bar/baz/breeze/boom', $this->testDirectory));
	}


	public function testCopyDirectoryMovesEntireDirectory()
	{
		mkdir($this->testDirectory.'/tmp', 0777, true);
		file_put_contents($this->testDirectory.'/tmp/foo.txt', '');
		file_put_contents($this->testDirectory.'/tmp/bar.txt', '');
		mkdir($this->testDirectory.'/tmp/nested', 0777, true);
		file_put_contents($this->testDirectory.'/tmp/nested/baz.txt', '');

		$files = new Filesystem;
		$files->copyDirectory($this->testDirectory.'/tmp', $this->testDirectory.'/tmp2');
		$this->assertTrue(is_dir($this->testDirectory.'/tmp2'));
		$this->assertFileExists($this->testDirectory.'/tmp2/foo.txt');
		$this->assertFileExists($this->testDirectory.'/tmp2/bar.txt');
		$this->assertTrue(is_dir($this->testDirectory.'/tmp2/nested'));
		$this->assertFileExists($this->testDirectory.'/tmp2/nested/baz.txt');

		unlink($this->testDirectory.'/tmp/nested/baz.txt');
		rmdir($this->testDirectory.'/tmp/nested');
		unlink($this->testDirectory.'/tmp/bar.txt');
		unlink($this->testDirectory.'/tmp/foo.txt');
		rmdir($this->testDirectory.'/tmp');

		unlink($this->testDirectory.'/tmp2/nested/baz.txt');
		rmdir($this->testDirectory.'/tmp2/nested');
		unlink($this->testDirectory.'/tmp2/foo.txt');
		unlink($this->testDirectory.'/tmp2/bar.txt');
		rmdir($this->testDirectory.'/tmp2');
	}

}
