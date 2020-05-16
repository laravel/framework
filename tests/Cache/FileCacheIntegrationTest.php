<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class FileCacheIntegrationTest extends TestCase
{
    public function testStoreWorksForDifferentUsers()
    {
        if (! extension_loaded('posix')) {
            $this->markTestSkipped('This test only applies when the posix extension is present');
        } elseif (posix_getuid() === 0) {
            $this->markTestSkipped('This test must run as a non-root user');
        }

        $dir = sys_get_temp_dir();
        $store = new FileStore(new Filesystem(), $dir, 0666);
        $store->flush();

        // Write a cache file (as non-root)
        $store->forever('foo', 'bar');

        // Change ownership of the cache file to root (user id 0)
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $cache_file = $dir.'/'.$cache_dir.'/'.$hash;
        exec('sudo chown root "'.$dir.'/'.$cache_dir.'/'.$hash.'"', $output, $exitCode);
        if ($exitCode !== 0) {
            $this->markTestSkipped('This test must run as a user with sudo access');
        }

        // Clear ownership information cached by PHP
        clearstatcache($cache_file);

        // Attempt to overwrite the cache file as non-root
        $this->assertTrue($store->forever('foo', 'bar'));
    }
}
