<?php

class SupportSlugifierTest extends PHPUnit_Framework_TestCase {

	public function testBasicUsage()
	{
		$this->assertEquals('this-is-my-blog-post', str_slug('This is my blog post!'));
		$this->assertEquals('voila-success', str_slug('Voilà, Success! '));
		$this->assertEquals('boy_bear', str_slug('   Boy & Bear. ', '_'));
	}

}
