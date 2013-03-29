<?php

use Mockery as m;
use Illuminate\Html\FormBuilder;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class FormBuilderTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->urlGenerator = new UrlGenerator(new RouteCollection, Request::create('/foo', 'GET'));
		$this->htmlBuilder = new HtmlBuilder($this->urlGenerator);
		$this->formBuilder =  new FormBuilder($this->htmlBuilder, $this->urlGenerator, '');
	}

	public function tearDown()
	{
		m::close();
	}


	public function testSelect()
	{
		$generated_html = $this->formBuilder->select(
			'size', 
			array('L' => 'Large', 'S' => 'Small')
		);
		$html = '<select name="size"><option value="L">Large</option><option value="S">Small</option></select>';
		$this->assertEquals($generated_html, $html);



		$generated_html = $this->formBuilder->select(
			'size',
			 array('L' => 'Large', 'S' => 'Small'), 
			 'L'
		);
		$html = '<select name="size"><option value="L" selected="selected">Large</option><option value="S">Small</option></select>';
		$this->assertEquals($generated_html, $html);



		$generated_html = $this->formBuilder->select(
			'size', 
			array('L' => 'Large', 'S' => 'Small'), 
			null,
			array('class' => 'class-name', 'id' => 'select-id')
		);
		$html = '<select class="class-name" id="select-id" name="size"><option value="L">Large</option><option value="S">Small</option></select>';
		$this->assertEquals($generated_html, $html);




		$model1 = new EloquentModelStub;
		$model1->id = 1;
		$model1->title = 'First option';
		$model2 = new EloquentModelStub;
		$model2->id = 2;
		$model2->title = 'Second option';
		$model3 = new EloquentModelStub;
		$model3->id = 3;
		$model3->title = 'Third option';
		$generated_html = $this->formBuilder->select_eloquent(
			'size', 
			array($model1, $model2, $model3), 
			array('id', 'title'),
			3,
			array('class' => 'class-name', 'id' => 'select-id')
		);
		$html = '<select class="class-name" id="select-id" name="size"><option value="1">First option</option><option value="2">Second option</option><option value="3" selected="selected">Third option</option></select>';
		$this->assertEquals($generated_html, $html);
	}
}


class EloquentModelStub extends Illuminate\Database\Eloquent\Model {
}