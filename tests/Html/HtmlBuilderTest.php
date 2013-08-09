<?php

use Mockery as m;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Symfony\Component\Routing\RouteCollection;

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

	public function testEntities()
	{
		$html1 = $this->htmlBuilder->entities('<h4>Taylor & Friends</h4>');

		$this->assertEquals('&lt;h4&gt;Taylor &amp; Friends&lt;/h4&gt;', $html1);
	}

	public function testEntityDecoding()
	{
		$html1 = $this->htmlBuilder->decode('&lt;h4&gt;Taylor &amp; Friends&lt;/h4&gt;');

		$this->assertEquals('<h4>Taylor & Friends</h4>', $html1);
	}

	public function testHtmlScript()
	{
		$html1 = $this->htmlBuilder->script("js/script.js");
		$html2 = $this->htmlBuilder->script("js/script.js", array('class' => 'tested'));

		$url = url("js/script.js");	// This gets our root url.

		// The EOL is expected behavior.
		$this->assertEquals('<script src="' . $url . '"></script>' . PHP_EOL, $html1);
		$this->assertEquals('<script class="tested" src="' . $url . '"></script>' . PHP_EOL, $html2);
	}

	public function testHtmlStyle()
	{
		$html1 = $this->htmlBuilder->style("css/main.css");
		$html2 = $this->htmlBuilder->style("css/main.css", array('media' => 'print'));
		$html3 = $this->htmlBuilder->style("css/main.css", array('class' => 'classy'));
		$html4 = $this->htmlBuilder->style("css/main.css", array('class' => 'classy', 'media' => 'print'));

		$url = url("css/main.css"); // This gets our root url.

		// The EOL is expected behavior.
		$this->assertEquals('<link media="all" type="text/css" rel="stylesheet" href="' . $url . '">' . PHP_EOL, $html1);
		$this->assertEquals('<link media="print" type="text/css" rel="stylesheet" href="' . $url . '">' . PHP_EOL, $html2);
		$this->assertEquals('<link class="classy" media="all" type="text/css" rel="stylesheet" href="' . $url . '">' . PHP_EOL, $html3);
		$this->assertEquals('<link class="classy" media="print" type="text/css" rel="stylesheet" href="' . $url . '">' . PHP_EOL, $html4);
	}

	public function testHtmlImage()
	{
		$html1 = $this->htmlBuilder->image('img/laravel.png');
		$html2 = $this->htmlBuilder->image('img/laravel.png', 'Laravel logo');
		$html3 = $this->htmlBuilder->image('img/laravel.png', 'Laravel logo', array('class' => 'logo'));

		$url = url("img/laravel.png"); // This gets our root url.

		$this->assertEquals('<img src="' . $url . '">', $html1);
		$this->assertEquals('<img src="' . $url . '" alt="Laravel logo">', $html2);
		$this->assertEquals('<img src="' . $url . '" class="logo" alt="Laravel logo">', $html3);
	}

	public function testHtmlLink()
	{
		$html1 = $this->htmlBuilder->link('user/profile');
		$html2 = $this->htmlBuilder->link('user/profile', 'User Profile');
		$html3 = $this->htmlBuilder->link('user/profile', 'User Profile', array('class' => 'btn'));
		$html4 = $this->htmlBuilder->link('user/profile', 'User Profile', array('class' => 'btn'), false);
		$html5 = $this->htmlBuilder->link('user/profile', 'User Profile', array('class' => 'btn'), true);

		$url = url("user/profile"); // This gets our root url.
		$secureUrl = url("user/profile", array(), true); // This gets our secure root url.

		$this->assertEquals('<a href="' . $url . '">' . $url . '</a>', $html1);
		$this->assertEquals('<a href="' . $url . '">User Profile</a>', $html2);
		$this->assertEquals('<a href="' . $url . '" class="btn">User Profile</a>', $html3);
		$this->assertEquals('<a href="' . $url . '" class="btn">User Profile</a>', $html4);
		$this->assertEquals('<a href="' . $secureUrl . '" class="btn">User Profile</a>', $html5);
	}

	public function testHtmlSecureLink()
	{
		$html1 = $this->htmlBuilder->secureLink('user/profile');
		$html2 = $this->htmlBuilder->secureLink('user/profile', 'User Profile');
		$html3 = $this->htmlBuilder->secureLink('user/profile', 'User Profile', array('class' => 'btn'));

		$secureUrl = url("user/profile", array(), true); // This gets our secure root url.

		$this->assertEquals('<a href="' . $secureUrl . '">' . $secureUrl . '</a>', $html1);
		$this->assertEquals('<a href="' . $secureUrl . '">User Profile</a>', $html2);
		$this->assertEquals('<a href="' . $secureUrl . '" class="btn">User Profile</a>', $html3);
	}

	public function testHtmlLinkAsset()
	{
		$html1 = $this->htmlBuilder->linkAsset('lib/item.pdf');
		$html2 = $this->htmlBuilder->linkAsset('lib/item.pdf', 'Item');
		$html3 = $this->htmlBuilder->linkAsset('lib/item.pdf', 'Item', array('class' => 'btn'));
		$html4 = $this->htmlBuilder->linkAsset('lib/item.pdf', 'Item', array('class' => 'btn'), false);
		$html5 = $this->htmlBuilder->linkAsset('lib/item.pdf', 'Item', array('class' => 'btn'), true);

		$url = url("lib/item.pdf"); // This gets our root url.
		$secureUrl = url("lib/item.pdf", array(), true); // This gets our secure root url.

		$this->assertEquals('<a href="' . $url . '">' . $url . '</a>', $html1);
		$this->assertEquals('<a href="' . $url . '">Item</a>', $html2);
		$this->assertEquals('<a href="' . $url . '" class="btn">Item</a>', $html3);
		$this->assertEquals('<a href="' . $url . '" class="btn">Item</a>', $html4);
		$this->assertEquals('<a href="' . $secureUrl . '" class="btn">Item</a>', $html5);
	}

	public function testHtmlLinkSecureAsset()
	{
		$html1 = $this->htmlBuilder->linkSecureAsset('lib/item.pdf');
		$html2 = $this->htmlBuilder->linkSecureAsset('lib/item.pdf', 'Item');
		$html3 = $this->htmlBuilder->linkSecureAsset('lib/item.pdf', 'Item', array('class' => 'btn'));

		$secureUrl = url("lib/item.pdf", array(), true); // This gets our secure root url.

		$this->assertEquals('<a href="' . $secureUrl . '">' . $secureUrl . '</a>', $html1);
		$this->assertEquals('<a href="' . $secureUrl . '">Item</a>', $html2);
		$this->assertEquals('<a href="' . $secureUrl . '" class="btn">Item</a>', $html3);
	}

	public function testHtmlLinkRoute()
	{
		// Suggestions on how to properly test this?
	}
	public function testHtmlLinkAction()
	{
		// Suggestions on how to properly test this?
	}
	public function testHtmlMailto()
	{
		$html1 = $this->htmlBuilder->mailto('laravel@laravel.com');
		$html2 = $this->htmlBuilder->mailto('laravel@laravel.com', 'Contact us!');
		$html3 = $this->htmlBuilder->mailto('laravel@laravel.com', 'Contact us!', array('class' => 'callout'));

		$this->assertEquals('<a href="mailto:laravel@laravel.com">laravel@laravel.com</a>', $this->htmlBuilder->decode($html1));
		$this->assertEquals('<a href="mailto:laravel@laravel.com">Contact us!</a>', $this->htmlBuilder->decode($html2));
		$this->assertEquals('<a href="mailto:laravel@laravel.com" class="callout">Contact us!</a>', $this->htmlBuilder->decode($html3));
	}
	public function testHtmlEmail()
	{
		$html1 = $this->htmlBuilder->email('laravel@laravel.com');
		$html2 = $this->htmlBuilder->email('laravel.admin@laravel.com');

		$this->assertEquals('laravel@laravel.com', $this->htmlBuilder->decode($html1));
		$this->assertEquals('laravel.admin@laravel.com', $this->htmlBuilder->decode($html2));
	}
	public function testHtmlOl()
	{
		$html1 = $this->htmlBuilder->ol(array('one', 'two', 'three'));
		$html2 = $this->htmlBuilder->ol(array('one' => 'word', 'two' => 'words', 'three' => 'wordss?'));
		$html3 = $this->htmlBuilder->ol(array('one', 'two', 'three'), array('class' => 'classy'));
		$html4 = $this->htmlBuilder->ol(array('one' => 'word', 'two' => 'words', 'three' => 'wordss?'), array('class' => 'classy'));

		$this->assertEquals('<ol><li>one</li><li>two</li><li>three</li></ol>', $html1);
		$this->assertEquals('<ol><li>word</li><li>words</li><li>wordss?</li></ol>', $html2);
		$this->assertEquals('<ol class="classy"><li>one</li><li>two</li><li>three</li></ol>', $html3);
		$this->assertEquals('<ol class="classy"><li>word</li><li>words</li><li>wordss?</li></ol>', $html4);
	}
	public function testHtmlUl()
	{
		$html1 = $this->htmlBuilder->ul(array('apple', 'banana', 'pear'));
		$html2 = $this->htmlBuilder->ul(array('primary' => 'lemon', 'secondary' => 'guava', 'tertiary' => 'orange'));
		$html3 = $this->htmlBuilder->ul(array('apple', 'banana', 'pear'), array('class' => 'classy'));
		$html4 = $this->htmlBuilder->ul(array('primary' => 'lemon', 'secondary' => 'guava', 'tertiary' => 'orange'), array('class' => 'classy'));

		$this->assertEquals('<ul><li>apple</li><li>banana</li><li>pear</li></ul>', $html1);
		$this->assertEquals('<ul><li>lemon</li><li>guava</li><li>orange</li></ul>', $html2);
		$this->assertEquals('<ul class="classy"><li>apple</li><li>banana</li><li>pear</li></ul>', $html3);
		$this->assertEquals('<ul class="classy"><li>lemon</li><li>guava</li><li>orange</li></ul>', $html4);
	}
	public function testHtmlAttributes()
	{
		$html1 = $this->htmlBuilder->attributes(array('class' => 'classy'));
		$html2 = $this->htmlBuilder->attributes(array('class' => 'classy', 'id' => 'primary'));
		$html3 = $this->htmlBuilder->attributes(array('class' => 'classy', 'required'));

		$this->assertEquals(' class="classy"', $html1);
		$this->assertEquals(' class="classy" id="primary"', $html2);
		$this->assertEquals(' class="classy" required="required"', $html3);
	}
	public function testHtmlObfuscate()
	{
		$html1 = $this->htmlBuilder->obfuscate('Randomly obfuscate plz.');

		$this->assertEquals('Randomly obfuscate plz.', $this->htmlBuilder->decode($html1));
	}
	public function testHtmlMacro()
	{
		$this->htmlBuilder->macro('html1', function() { return '<span class="html1">'; });
		$this->htmlBuilder->macro('html2', function() { return '<span class="html2"></span>'; });
		$this->htmlBuilder->macro('html3', function($content) { return '<span class="html3">' . $content . '</span>'; });
		$this->htmlBuilder->macro('html4', function($content, $attributes = null) {
			return '<span class="html4"' . HTML::attributes($attributes) . '>' . $content . '</span>';
		});

		$html1 = $this->htmlBuilder->html1();
		$html2 = $this->htmlBuilder->html2();
		$html3 = $this->htmlBuilder->html3('Hello Laravel!');
		$html4 = $this->htmlBuilder->html4('Hello Laravel!', array('id' => 'intro'));

		$this->assertEquals('<span class="html1">', $html1);
		$this->assertEquals('<span class="html2"></span>', $html2);
		$this->assertEquals('<span class="html3">Hello Laravel!</span>', $html3);
		$this->assertEquals('<span class="html4" id="intro">Hello Laravel!</span>', $html4);
	}
}