<?php

use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase {

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
		$this->assertEquals('Hello World', file_get_contents(__DIR__.'/file.txt'));
		@unlink(__DIR__.'/file.txt');
	}


	public function testDeleteRemovesFiles()
	{
		file_put_contents(__DIR__.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->delete(__DIR__.'/file.txt');
		$this->assertFalse(file_exists(__DIR__.'/file.txt'));
		@unlink(__DIR__.'/file.txt');
	}


	public function testDeleteDirectory()
	{
		mkdir(__DIR__.'/foo');
		file_put_contents(__DIR__.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->deleteDirectory(__DIR__.'/foo');
		$this->assertFalse(is_dir(__DIR__.'/foo'));
		$this->assertFalse(file_exists(__DIR__.'/foo/file.txt'));
	}


	public function testCleanDirectory()
	{
		mkdir(__DIR__.'/foo');
		file_put_contents(__DIR__.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->cleanDirectory(__DIR__.'/foo');
		$this->assertTrue(is_dir(__DIR__.'/foo'));
		$this->assertFalse(file_exists(__DIR__.'/foo/file.txt'));
		@rmdir(__DIR__.'/foo');
	}


	public function testFilesMethod()
	{
		mkdir(__DIR__.'/foo');
		file_put_contents(__DIR__.'/foo/1.txt', '1');
		file_put_contents(__DIR__.'/foo/2.txt', '2');
		mkdir(__DIR__.'/foo/bar');
		$files = new Filesystem;
		$this->assertEquals(array(__DIR__.'/foo/1.txt', __DIR__.'/foo/2.txt'), $files->files(__DIR__.'/foo'));
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
		$this->assertTrue(file_exists(__DIR__.'/tmp2/foo.txt'));
		$this->assertTrue(file_exists(__DIR__.'/tmp2/bar.txt'));
		$this->assertTrue(is_dir(__DIR__.'/tmp2/nested'));
		$this->assertTrue(file_exists(__DIR__.'/tmp2/nested/baz.txt'));

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

}