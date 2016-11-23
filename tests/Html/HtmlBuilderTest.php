<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;

class HtmlBuilderTest extends PHPUnit_Framework_TestCase {

	/**
	 * Setup the test environment.
	 */
	public function setUp()
	{
		$this->urlGenerator = new UrlGenerator(new RouteCollection, Request::create('/foo', 'GET'));
		$this->htmlBuilder = new HtmlBuilder($this->urlGenerator);
	}


	/**
	 * Destroy the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}


	public function testTag()
	{
		$tag1 = $this->htmlBuilder->tag('br');
		$tag2 = $this->htmlBuilder->tag('br', null, true);
		$tag3 = $this->htmlBuilder->tag('input', array('type' => 'text', 'required'));
		$tag4 = $this->htmlBuilder->tag('img', array('src' => 'test.png'), false);
		$tag5 = $this->htmlBuilder->tag('div', array('data-language' => 'PHP', 'data-framework' => 'Laravel'));

		$this->assertEquals('<br />', $tag1);
		$this->assertEquals('<br>', $tag2);
		$this->assertEquals('<input type="text" required="required" />', $tag3);
		$this->assertEquals('<img src="test.png" />', $tag4);
		$this->assertEquals('<div data-language="PHP" data-framework="Laravel" />', $tag5);
	}


	public function testContentTag()
	{
		$tag1 = $this->htmlBuilder->contentTag('p', 'Hello world');
		$tag2 = $this->htmlBuilder->contentTag('div', $this->htmlBuilder->contentTag('p', 'Hello world'), array('class' => 'hide'));
		$tag3 = $this->htmlBuilder->contentTag('div', $this->htmlBuilder->contentTag('p', 'Hello world'), array('class' => 'hide'), false);
		$tag4 = $this->htmlBuilder->contentTag('div');

		$this->assertEquals('<p>Hello world</p>', $tag1);
		$this->assertEquals('<div class="hide">&lt;p&gt;Hello world&lt;/p&gt;</div>', $tag2);
		$this->assertEquals('<div class="hide"><p>Hello world</p></div>', $tag3);
		$this->assertEquals('<div></div>', $tag4);
	}
}
