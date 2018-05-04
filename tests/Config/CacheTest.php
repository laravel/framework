<?php

namespace Illuminate\Tests\Config;

use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Console\ConfigCacheCommand;

class CacheTest extends RepositoryTest
{
    public function setUp()
    {
        parent::setUp();

        // we need to pull the existing repository config from RepositoryTest.
        $this->config = ConfigCacheCommand::transformClosure($this->repository->all());

        // we need to re-set the repository values using the closure
        // since we need to test get() and getMany() methods of the config repository.
        $this->repository = new Repository($this->config);
    }

    public function testHasSuperClosureStr()
    {
        $this->assertTrue(
            strpos($this->config['callback_array'], '"SuperClosure\\SerializableClosure"') !== false
        );
    }

    // public function testCallbackArray() {}
    // public function testCallbackInstance() {}
    // public function testConstruct() {}
    // public function testHasIsTrue() {}
    // public function testHasIsFalse() {}
    // public function testGet() {}
    // public function testGetWithArrayOfKeys() {}
    // public function testGetMany() {}
    // public function testGetWithDefault() {}
    // public function testSet() {}
    // public function testSetArray() {}
    // public function testPrepend() {}
    // public function testPush() {}
    // public function testAll() {}
    // public function testOffsetUnset() {}
}
