<?php

use Mockery as m;
use Illuminate\Html\FormBuilder;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Symfony\Component\Routing\RouteCollection;

class FormBuilderTest extends PHPUnit_Framework_TestCase {

	/**
	 * Setup the test environment.
	 */
	public function setUp()
	{
		$this->urlGenerator = new UrlGenerator(new RouteCollection, Request::create('/foo', 'GET'));
		$this->htmlBuilder = new HtmlBuilder($this->urlGenerator);
		$this->formBuilder =  new FormBuilder($this->htmlBuilder, $this->urlGenerator, '');
	}

	/**
	 * Destroy the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}


	public function testOpeningForm()
	{
		$form1 = $this->formBuilder->open(array('method' => 'GET'));
		$form2 = $this->formBuilder->open(array('method' => 'POST', 'class' => 'form', 'id' => 'id-form'));
		$form3 = $this->formBuilder->open(array('method' => 'GET', 'accept-charset' => 'UTF-16'));
		$form4 = $this->formBuilder->open(array('method' => 'GET', 'accept-charset' => 'UTF-16', 'files' => true));


		$this->assertEquals('<form method="GET" action="http://localhost/foo" accept-charset="UTF-8">', $form1);
		$this->assertEquals('<form method="POST" action="http://localhost/foo" accept-charset="UTF-8" class="form" id="id-form">', $form2);
		$this->assertEquals('<form method="GET" action="http://localhost/foo" accept-charset="UTF-16">', $form3);
		$this->assertEquals('<form method="GET" action="http://localhost/foo" accept-charset="UTF-16" enctype="multipart/form-data">', $form4);
	}


	public function testClosingForm()
	{
		$this->assertEquals('</form>', $this->formBuilder->close());
	}


	public function testFormLabel()
	{
		$form1 = $this->formBuilder->label('foo', 'Foobar');
		$form2 = $this->formBuilder->label('foo', 'Foobar', array('class' => 'control-label'));

		$this->assertEquals('<label for="foo">Foobar</label>', $form1);
		$this->assertEquals('<label for="foo" class="control-label">Foobar</label>', $form2);
	}


	public function testFormInput()
	{
		$form1 = $this->formBuilder->input('text', 'foo');
		$form2 = $this->formBuilder->input('text', 'foo', 'foobar');
		$form3 = $this->formBuilder->input('date', 'foobar', null, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="text">', $form1);
		$this->assertEquals('<input name="foo" type="text" value="foobar">', $form2);
		$this->assertEquals('<input class="span2" name="foobar" type="date">', $form3);
	}


	public function testFormText()
	{
		$form1 = $this->formBuilder->input('text', 'foo');
		$form2 = $this->formBuilder->text('foo');
		$form3 = $this->formBuilder->text('foo', 'foobar');
		$form4 = $this->formBuilder->text('foo', null, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="text">', $form1);
		$this->assertEquals($form1, $form2);
		$this->assertEquals('<input name="foo" type="text" value="foobar">', $form3);
		$this->assertEquals('<input class="span2" name="foo" type="text">', $form4);
	}


	public function testFormPassword()
	{
		$form1 = $this->formBuilder->password('foo');
		$form2 = $this->formBuilder->password('foo', array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="password" value="">', $form1);
		$this->assertEquals('<input class="span2" name="foo" type="password" value="">', $form2);
	}


	public function testFormHidden()
	{
		$form1 = $this->formBuilder->hidden('foo');
		$form2 = $this->formBuilder->hidden('foo', 'foobar');
		$form3 = $this->formBuilder->hidden('foo', null, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="hidden">', $form1);
		$this->assertEquals('<input name="foo" type="hidden" value="foobar">', $form2);
		$this->assertEquals('<input class="span2" name="foo" type="hidden">', $form3);
	}


	public function testFormEmail()
	{
		$form1 = $this->formBuilder->email('foo');
		$form2 = $this->formBuilder->email('foo', 'foobar');
		$form3 = $this->formBuilder->email('foo', null, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="email">', $form1);
		$this->assertEquals('<input name="foo" type="email" value="foobar">', $form2);
		$this->assertEquals('<input class="span2" name="foo" type="email">', $form3);
	}


	public function testFormFile()
	{
		$form1 = $this->formBuilder->file('foo');
		$form2 = $this->formBuilder->file('foo', array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="file">', $form1);
		$this->assertEquals('<input class="span2" name="foo" type="file">', $form2);
	}


	public function testFormTextarea()
	{
		$form1 = $this->formBuilder->textarea('foo');
		$form2 = $this->formBuilder->textarea('foo', 'foobar');
		$form3 = $this->formBuilder->textarea('foo', null, array('class' => 'span2'));

		$this->assertEquals('<textarea name="foo" cols="50" rows="10"></textarea>', $form1);
		$this->assertEquals('<textarea name="foo" cols="50" rows="10">foobar</textarea>', $form2);
		$this->assertEquals('<textarea class="span2" name="foo" cols="50" rows="10"></textarea>', $form3);
	}


	public function testSelect()
	{
		$select = $this->formBuilder->select(
			'size', 
			array('L' => 'Large', 'S' => 'Small')
		);
		$this->assertEquals($select, '<select name="size"><option value="L">Large</option><option value="S">Small</option></select>');



		$select = $this->formBuilder->select(
			'size',
			 array('L' => 'Large', 'S' => 'Small'), 
			 'L'
		);
		$this->assertEquals($select, '<select name="size"><option value="L" selected="selected">Large</option><option value="S">Small</option></select>');



		$select = $this->formBuilder->select(
			'size', 
			array('L' => 'Large', 'S' => 'Small'), 
			null,
			array('class' => 'class-name', 'id' => 'select-id')
		);
		$this->assertEquals($select, '<select class="class-name" id="select-id" name="size"><option value="L">Large</option><option value="S">Small</option></select>');
	}


	public function testFormCheckbox()
	{
		$form1 = $this->formBuilder->input('checkbox', 'foo');
		$form2 = $this->formBuilder->checkbox('foo');
		$form3 = $this->formBuilder->checkbox('foo', 'foobar', true);
		$form4 = $this->formBuilder->checkbox('foo', 'foobar', false, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="checkbox">', $form1);
		$this->assertEquals('<input name="foo" type="checkbox" value="1">', $form2);
		$this->assertEquals('<input checked="checked" name="foo" type="checkbox" value="foobar">', $form3);
		$this->assertEquals('<input class="span2" name="foo" type="checkbox" value="foobar">', $form4);
	}


	public function testFormRadio()
	{
		$form1 = $this->formBuilder->input('radio', 'foo');
		$form2 = $this->formBuilder->radio('foo');
		$form3 = $this->formBuilder->radio('foo', 'foobar', true);
		$form4 = $this->formBuilder->radio('foo', 'foobar', false, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="radio">', $form1);
		$this->assertEquals('<input name="foo" type="radio" value="foo">', $form2);
		$this->assertEquals('<input checked="checked" name="foo" type="radio" value="foobar">', $form3);
		$this->assertEquals('<input class="span2" name="foo" type="radio" value="foobar">', $form4);
	}


	public function testFormSubmit()
	{
		$form1 = $this->formBuilder->submit('foo');
		$form2 = $this->formBuilder->submit('foo', array('class' => 'span2'));

		$this->assertEquals('<input type="submit" value="foo">', $form1);
		$this->assertEquals('<input class="span2" type="submit" value="foo">', $form2);
	}



	public function testFormButton()
	{
		$form1 = $this->formBuilder->button('foo');
		$form2 = $this->formBuilder->button('foo', array('class' => 'span2'));

		$this->assertEquals('<button type="button">foo</button>', $form1);
		$this->assertEquals('<button class="span2" type="button">foo</button>', $form2);
	}
}
