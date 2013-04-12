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
		$this->htmlBuilder = new HtmlBuilder($this->urlGenerator, $this->getHtmlConfig());
	}

	/**
	 * Destroy the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}


	public function testDoctype()
	{
		$doc1 = $this->htmlBuilder->doctype('html5');
		$doc2 = $this->htmlBuilder->doctype('html-transitional');
		$doc3 = $this->htmlBuilder->doctype('xhtml-mp12');

		$this->assertEquals('<!DOCTYPE html>', $doc1);
		$this->assertEquals('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">', $doc2);
		$this->assertEquals('<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">', $doc3);
	}


	public function testStyle()
	{
		$stl1 = $this->htmlBuilder->style('http://laravel.com/style.css');
		$stl2 = $this->htmlBuilder->style('http://laravel.com/style.css', array('media' => 'handheld'));

		$this->htmlBuilder->setMarkup('xhtml');

		$stl3 = $this->htmlBuilder->style('http://laravel.com/style.css', array('media' => 'handheld'));

		$this->assertEquals('<link media="all" type="text/css" rel="stylesheet" href="http://laravel.com/style.css">'.PHP_EOL, $stl1);
		$this->assertEquals('<link media="handheld" type="text/css" rel="stylesheet" href="http://laravel.com/style.css">'.PHP_EOL, $stl2);
		$this->assertEquals('<link media="handheld" type="text/css" rel="stylesheet" href="http://laravel.com/style.css"/>'.PHP_EOL, $stl3);
	}


	public function testImage()
	{
		$img1 = $this->htmlBuilder->image('http://laravel.com/laravel.ico');
		$img2 = $this->htmlBuilder->image('http://laravel.com/laravel.ico', 'Icon');

		$this->htmlBuilder->setMarkup('xhtml');

		$img3 = $this->htmlBuilder->image('http://laravel.com/laravel.ico', 'Icon', array('id' => 'img'));

		$this->assertEquals('<img src="http://laravel.com/laravel.ico">', $img1);
		$this->assertEquals('<img src="http://laravel.com/laravel.ico" alt="Icon">', $img2);
		$this->assertEquals('<img src="http://laravel.com/laravel.ico" id="img" alt="Icon"/>', $img3);
	}


	public function getHtmlConfig()
	{
		return array(

			/*
			|--------------------------------------------------------------------------
			| Markup
			|--------------------------------------------------------------------------
			|
			| Here you may specify the markup for HTML Builder and Form builder for
			| correct tags rendering.
			|
			| Supported: 'html', 'xhtml'
			|
			*/

			'markup' => 'html',


			/*
			|--------------------------------------------------------------------------
			| Document Type Declaration
			|--------------------------------------------------------------------------
			|
			| Here you can specify the default document type, which will be used
			| with HTML::doctype
			|
			*/

			'doctype' => 'html5',


			/*
			|--------------------------------------------------------------------------
			| List Of Doctypes
			|--------------------------------------------------------------------------
			|
			| Taken from:
			|  - http://www.w3schools.com/tags/tag_doctype.asp
			|  - http://en.wikipedia.org/wiki/Document_type_declaration
			|
			*/

			'doctypes' => array(

				'html5'              => '<!DOCTYPE html>',

				'html-strict'        => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
				'html-transitional'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
				'html-frameset'      => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',

				'xhtml'              => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
				'xhtml-strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
				'xhtml-transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
				'xhtml-frameset'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',

				'xhtml-basic'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
				'xhtml-mp10'         => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">',
				'xhtml-mp11'         => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.1//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile11.dtd">',
				'xhtml-mp12'         => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'

			)

		);
	}

}
