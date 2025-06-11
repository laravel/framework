<?php

namespace Illuminate\Tests\Session;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

use function Illuminate\Filesystem\join_paths;

class FileSessionHandlerTest extends TestCase
{
    protected $files;

    protected $sessionHandler;

    protected function setUp(): void
    {
        // Create a mock for the Filesystem class
        $this->files = Mockery::mock(Filesystem::class);

        // Initialize the FileSessionHandler with the mocked Filesystem
        $this->sessionHandler = new FileSessionHandler($this->files, '/path/to/sessions', 30);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_open()
    {
        $this->assertTrue($this->sessionHandler->open('/path/to/sessions', 'session_name'));
    }

    public function test_close()
    {
        $this->assertTrue($this->sessionHandler->close());
    }

    public function test_read_returns_data_when_file_exists_and_is_valid()
    {
        $sessionId = 'session_id';
        $path = '/path/to/sessions/'.$sessionId;
        Carbon::setTestNow(Carbon::parse('2025-02-02 01:30:00'));
        // Set up expectations
        $this->files->shouldReceive('isFile')->with($path)->andReturn(true);

        $minutesAgo30 = Carbon::parse('2025-02-02 01:00:00')->getTimestamp();
        $this->files->shouldReceive('lastModified')->with($path)->andReturn($minutesAgo30);
        $this->files->shouldReceive('sharedGet')->with($path)->once()->andReturn('session_data');

        $result = $this->sessionHandler->read($sessionId);

        $this->assertEquals('session_data', $result);
    }

    public function test_read_returns_data_when_file_exists_but_expired()
    {
        $sessionId = 'session_id';
        $path = '/path/to/sessions/'.$sessionId;
        Carbon::setTestNow(Carbon::parse('2025-02-02 01:30:01'));
        // Set up expectations
        $this->files->shouldReceive('isFile')->with($path)->andReturn(true);

        $minutesAgo30 = Carbon::parse('2025-02-02 01:00:00')->getTimestamp();
        $this->files->shouldReceive('lastModified')->with($path)->andReturn($minutesAgo30);
        $this->files->shouldReceive('sharedGet')->never();

        $result = $this->sessionHandler->read($sessionId);

        $this->assertEquals('', $result);
    }

    public function test_read_returns_empty_string_when_file_does_not_exist()
    {
        $sessionId = 'non_existing_session_id';
        $path = '/path/to/sessions/'.$sessionId;

        // Set up expectations
        $this->files->shouldReceive('isFile')->with($path)->andReturn(false);

        $result = $this->sessionHandler->read($sessionId);

        $this->assertEquals('', $result);
    }

    public function test_write_stores_data()
    {
        $sessionId = 'session_id';
        $data = 'session_data';

        // Set up expectations
        $this->files->shouldReceive('put')->with('/path/to/sessions/'.$sessionId, $data, true)->once()->andReturn(null);

        $result = $this->sessionHandler->write($sessionId, $data);

        $this->assertTrue($result);
    }

    public function test_destroy_deletes_session_file()
    {
        $sessionId = 'session_id';

        // Set up expectations
        $this->files->shouldReceive('delete')->with('/path/to/sessions/'.$sessionId)->once()->andReturn(null);

        $result = $this->sessionHandler->destroy($sessionId);

        $this->assertTrue($result);
    }

    public function test_gc_deletes_old_session_files()
    {
        $session = new FileSessionHandler($this->files, join_paths(__DIR__, 'tmp'), 30);
        // Set up expectations for Filesystem
        $this->files->shouldReceive('delete')->with(join_paths(__DIR__, 'tmp', 'a2'))->once()->andReturn(false);
        $this->files->shouldReceive('delete')->with(join_paths(__DIR__, 'tmp', 'a3'))->once()->andReturn(true);

        mkdir(__DIR__.'/tmp');
        touch(__DIR__.'/tmp/a1', time() - 3); // last modified: 3 sec ago
        touch(__DIR__.'/tmp/a2', time() - 5); // last modified: 5 sec ago
        touch(__DIR__.'/tmp/a3', time() - 7); // last modified: 7 sec ago

        // act:
        $count = $session->gc(5);

        $this->assertEquals(2, $count);

        unlink(__DIR__.'/tmp/a1');
        unlink(__DIR__.'/tmp/a2');
        unlink(__DIR__.'/tmp/a3');

        rmdir(__DIR__.'/tmp');
    }
}
