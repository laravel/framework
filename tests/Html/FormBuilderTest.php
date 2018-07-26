<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Html\FormBuilder;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;

class FormBuilderTest extends TestCase {

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
		$form5 = $this->formBuilder->open(array('method' => 'PUT'));


		$this->assertEquals('<form method="GET" action="http://localhost/foo" accept-charset="UTF-8">', $form1);
		$this->assertEquals('<form method="POST" action="http://localhost/foo" accept-charset="UTF-8" class="form" id="id-form"><input name="_token" type="hidden" value="">', $form2);
		$this->assertEquals('<form method="GET" action="http://localhost/foo" accept-charset="UTF-16">', $form3);
		$this->assertEquals('<form method="GET" action="http://localhost/foo" accept-charset="UTF-16" enctype="multipart/form-data">', $form4);
		$this->assertEquals('<form method="POST" action="http://localhost/foo" accept-charset="UTF-8"><input name="_method" type="hidden" value="PUT"><input name="_token" type="hidden" value="">', $form5);
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


	public function testPasswordsNotFilled()
	{
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));

		$session->shouldReceive('getOldInput')->never();

		$form1 = $this->formBuilder->password('password');

		$this->assertEquals('<input name="password" type="password" value="">', $form1);
	}


	public function testFilesNotFilled()
	{
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));

		$session->shouldReceive('getOldInput')->never();

		$form = $this->formBuilder->file('img');

		$this->assertEquals('<input name="img" type="file">', $form);
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


	public function testFormTextRepopulation()
	{
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));
		$this->setModel($model = array('relation' => array('key' => 'attribute'), 'other' => 'val'));

		$session->shouldReceive('getOldInput')->twice()->with('name_with_dots')->andReturn('some value');
		$input = $this->formBuilder->text('name.with.dots', 'default value');
		$this->assertEquals('<input name="name.with.dots" type="text" value="some value">', $input);

		$session->shouldReceive('getOldInput')->once()->with('text.key.sub')->andReturn(null);
		$input = $this->formBuilder->text('text[key][sub]', 'default value');
		$this->assertEquals('<input name="text[key][sub]" type="text" value="default value">', $input);

		$session->shouldReceive('getOldInput')->with('relation.key')->andReturn(null);
		$input1 = $this->formBuilder->text('relation[key]');

		$this->setModel($model, false);
		$input2 = $this->formBuilder->text('relation[key]');

		$this->assertEquals('<input name="relation[key]" type="text" value="attribute">', $input1);
		$this->assertEquals($input1, $input2);
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
		$form4 = $this->formBuilder->textarea('foo', null, array('size' => '60x15'));

		$this->assertEquals('<textarea name="foo" cols="50" rows="10"></textarea>', $form1);
		$this->assertEquals('<textarea name="foo" cols="50" rows="10">foobar</textarea>', $form2);
		$this->assertEquals('<textarea class="span2" name="foo" cols="50" rows="10"></textarea>', $form3);
		$this->assertEquals('<textarea name="foo" cols="60" rows="15"></textarea>', $form4);
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



		$this->formBuilder->label('select-name-id');
		$select = $this->formBuilder->select(
			'select-name-id',
			array(),
			null,
			array('name' => 'select-name')
		);
		$this->assertEquals($select, '<select name="select-name" id="select-name-id"></select>');
	}


	public function testFormSelectRepopulation()
	{
		$list = array('L' => 'Large', 'M' => 'Medium', 'S' => 'Small');
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));
		$this->setModel($model = array('size' => array('key' => 'S'), 'other' => 'val'));

		$session->shouldReceive('getOldInput')->twice()->with('size')->andReturn('M');
		$select = $this->formBuilder->select('size', $list, 'S');
		$this->assertEquals($select, '<select name="size"><option value="L">Large</option><option value="M" selected="selected">Medium</option><option value="S">Small</option></select>');

		$session->shouldReceive('getOldInput')->twice()->with('size.multi')->andReturn(array('L', 'S'));
		$select = $this->formBuilder->select('size[multi][]', $list, 'M', array('multiple' => 'multiple'));
		$this->assertEquals($select, '<select multiple="multiple" name="size[multi][]"><option value="L" selected="selected">Large</option><option value="M">Medium</option><option value="S" selected="selected">Small</option></select>');

		$session->shouldReceive('getOldInput')->once()->with('size.key')->andReturn(null);
		$select = $this->formBuilder->select('size[key]', $list);
		$this->assertEquals($select, '<select name="size[key]"><option value="L">Large</option><option value="M">Medium</option><option value="S" selected="selected">Small</option></select>');
	}


	public function testFormSelectYear()
	{
		$select1 = $this->formBuilder->selectYear('year', 2000, 2020);
		$select2 = $this->formBuilder->selectYear('year', 2000, 2020, null, array('id' => 'foo'));
		$select3 = $this->formBuilder->selectYear('year', 2000, 2020, '2000');

		$this->assertContains('<select name="year"><option value="2000">2000</option><option value="2001">2001</option>', $select1);
		$this->assertContains('<select id="foo" name="year"><option value="2000">2000</option><option value="2001">2001</option>', $select2);
		$this->assertContains('<select name="year"><option value="2000" selected="selected">2000</option><option value="2001">2001</option>', $select3);
	}


	public function testFormSelectRange()
	{
		$range = $this->formBuilder->selectRange('dob', 1900, 2013);

		$this->assertContains('<select name="dob"><option value="1900">1900</option>', $range);
		$this->assertContains('<option value="2013">2013</option>', $range);
	}


	public function testFormSelectMonth()
	{
		$month1 = $this->formBuilder->selectMonth('month');
		$month2 = $this->formBuilder->selectMonth('month', '1');
		$month3 = $this->formBuilder->selectMonth('month', null, array('id' => 'foo'));

		$this->assertContains('<select name="month"><option value="1">January</option><option value="2">February</option>', $month1);
		$this->assertContains('<select name="month"><option value="1" selected="selected">January</option>', $month2);
		$this->assertContains('<select id="foo" name="month"><option value="1">January</option>', $month3);
	}


	public function testFormCheckbox()
	{
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));

		$session->shouldReceive('getOldInput')->withNoArgs()->andReturn(array());
		$session->shouldReceive('getOldInput')->with('foo')->andReturn(null);

		$form1 = $this->formBuilder->input('checkbox', 'foo');
		$form2 = $this->formBuilder->checkbox('foo');
		$form3 = $this->formBuilder->checkbox('foo', 'foobar', true);
		$form4 = $this->formBuilder->checkbox('foo', 'foobar', false, array('class' => 'span2'));

		$this->assertEquals('<input name="foo" type="checkbox">', $form1);
		$this->assertEquals('<input name="foo" type="checkbox" value="1">', $form2);
		$this->assertEquals('<input checked="checked" name="foo" type="checkbox" value="foobar">', $form3);
		$this->assertEquals('<input class="span2" name="foo" type="checkbox" value="foobar">', $form4);
	}


	public function testFormCheckboxRepopulation()
	{
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));
		$session->shouldReceive('getOldInput')->withNoArgs()->andReturn(array(1));

		$session->shouldReceive('getOldInput')->once()->with('check')->andReturn(null);
		$check = $this->formBuilder->checkbox('check', 1, true);
		$this->assertEquals('<input name="check" type="checkbox" value="1">', $check);

		$session->shouldReceive('getOldInput')->with('check.key')->andReturn('yes');
		$check = $this->formBuilder->checkbox('check[key]', 'yes');
		$this->assertEquals('<input checked="checked" name="check[key]" type="checkbox" value="yes">', $check);

		$session->shouldReceive('getOldInput')->with('multicheck')->andReturn(array(1, 3));
		$check1 = $this->formBuilder->checkbox('multicheck[]', 1);
		$check2 = $this->formBuilder->checkbox('multicheck[]', 2, true);
		$check3 = $this->formBuilder->checkbox('multicheck[]', 3);

		$this->assertEquals('<input checked="checked" name="multicheck[]" type="checkbox" value="1">', $check1);
		$this->assertEquals('<input name="multicheck[]" type="checkbox" value="2">', $check2);
		$this->assertEquals('<input checked="checked" name="multicheck[]" type="checkbox" value="3">', $check3);
	}


	public function testFormCheckboxWithoutSession()
	{
		$form1 = $this->formBuilder->checkbox('foo');
		$form2 = $this->formBuilder->checkbox('foo', 'foobar', true);

		$this->assertEquals('<input name="foo" type="checkbox" value="1">', $form1);
		$this->assertEquals('<input checked="checked" name="foo" type="checkbox" value="foobar">', $form2);
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


	public function testFormRadioRepopulation()
	{
		$this->formBuilder->setSessionStore($session = m::mock('Illuminate\Session\Store'));

		$session->shouldReceive('getOldInput')->with('radio')->andReturn(1);

		$radio1 = $this->formBuilder->radio('radio', 1);
		$radio2 = $this->formBuilder->radio('radio', 2, true);

		$this->assertEquals('<input checked="checked" name="radio" type="radio" value="1">', $radio1);
		$this->assertEquals('<input name="radio" type="radio" value="2">', $radio2);
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


	public function testResetInput()
	{
		$resetInput = $this->formBuilder->reset('foo');
		$this->assertEquals('<input type="reset" value="foo">', $resetInput);
	}


	public function testImageInput()
	{
		$url = 'http://laravel.com/';
		$image = $this->formBuilder->image($url);

		$this->assertEquals('<input src="'. $url .'" type="image">', $image);
	}

	protected function setModel(array $data, $object = true)
	{
		if ($object) $data = new FormBuilderModelStub($data);

		$this->formBuilder->model($data, array('method' => 'GET'));
	}
}

class FormBuilderModelStub {

	protected $data;

	public function __construct(array $data = array())
	{
		foreach ($data as $key => $val)
		{
			if (is_array($val)) $val = new self($val);

			$this->data[$key] = $val;
		}
	}


	public function __get($key)
	{
		return $this->data[$key];
	}


	public function __isset($key)
	{
		return isset($this->data[$key]);
	}
}
