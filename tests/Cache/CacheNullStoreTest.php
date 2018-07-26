<?php

use Illuminate\Cache\NullStore;

class CacheNullStoreTest extends TestCase {

	public function testItemsCanNotBeCached()
	{
		$store = new NullStore;
		$store->put('foo', 'bar', 10);
		$this->assertNull($store->get('foo'));
	}

}
