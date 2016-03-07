<?php

use Mockery as m;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class FoundationInteractsWithPagesUnitTest extends PHPUnit_Framework_TestCase
{
    use MakesHttpRequests;

    public function setUp()
    {
        $this->crawler = m::mock(Crawler::class)->makePartial();
    }

    public function tearDown()
    {
        m::close();
    }

    protected function mockInput($value)
    {
        $input = m::mock(Crawler::class)->makePartial();
        $input->shouldReceive('count')->andReturn(1);
        $input->shouldReceive('nodeName')->once()->andReturn('input');
        $input->shouldReceive('attr')
            ->withArgs(['value'])
            ->once()
            ->andReturn($value);

        return $input;
    }

    public function testSeeInFieldInput()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#framework, input[name='framework'], textarea#framework, textarea[name='framework']"])
            ->once()
            ->andReturn($this->mockInput('Laravel'));

        $this->seeInField('framework', 'Laravel');
    }

    public function testDontSeeInFieldInput()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#framework, input[name='framework'], textarea#framework, textarea[name='framework']"])
            ->once()
            ->andReturn($this->mockInput('Laravel'));

        $this->dontSeeInField('framework', 'Rails');
    }

    public function testSeeInFieldInputArray()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#framework\\[\\], input[name='framework[]'], textarea#framework\\[\\], textarea[name='framework[]']"])
            ->once()
            ->andReturn($this->mockInput('Laravel'));

        $this->seeInField('framework[]', 'Laravel');
    }

    public function testDontSeeInFieldInputArray()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#framework\\[\\], input[name='framework[]'], textarea#framework\\[\\], textarea[name='framework[]']"])
            ->once()
            ->andReturn($this->mockInput('Laravel'));

        $this->dontSeeInField('framework[]', 'Rails');
    }

    protected function mockTextarea($value)
    {
        $textarea = m::mock(Crawler::class)->makePartial();
        $textarea->shouldReceive('count')->andReturn(1);
        $textarea->shouldReceive('nodeName')->once()->andReturn('textarea');
        $textarea->shouldReceive('text')->once()->andReturn($value);

        return $textarea;
    }

    public function testSeeInFieldTextarea()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#description, input[name='description'], textarea#description, textarea[name='description']"])
            ->once()
            ->andReturn($this->mockTextarea('Laravel is awesome'));

        $this->seeInField('description', 'Laravel is awesome');
    }

    public function testDontSeeInFieldTextarea()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#description, input[name='description'], textarea#description, textarea[name='description']"])
            ->once()
            ->andReturn($this->mockTextarea('Laravel is awesome'));

        $this->dontSeeInField('description', 'Rails is awesome');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Given selector [select] is not an input or textarea
     */
    public function testSeeInFieldWrongElementException()
    {
        $select = m::mock(Crawler::class)->makePartial();
        $select->shouldReceive('count')->andReturn(1);
        $select->shouldReceive('nodeName')->once()->andReturn('select');

        $this->crawler->shouldReceive('filter')
            ->withArgs(["input#select, input[name='select'], textarea#select, textarea[name='select']"])
            ->once()
            ->andReturn($select);

        $this->seeInField('select', 'selected_value');
    }

    protected function mockCheckbox($checked = true)
    {
        $checkbox = m::mock(Crawler::class)->makePartial();
        $checkbox->shouldReceive('count')->andReturn(1);
        $checkbox->shouldReceive('attr')
            ->withArgs(['checked'])
            ->once()
            ->andReturn($checked ? 'checked' : null);

        return $checkbox;
    }

    public function testSeeIsChecked()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input[type='checkbox']#terms, input[type='checkbox'][name='terms']"])
            ->once()
            ->andReturn($this->mockCheckbox(true));

        $this->seeIsChecked('terms');
    }

    public function testDontSeeIsChecked()
    {
        $this->crawler->shouldReceive('filter')
            ->withArgs(["input[type='checkbox']#terms, input[type='checkbox'][name='terms']"])
            ->once()
            ->andReturn($this->mockCheckbox(false));

        $this->dontSeeIsChecked('terms');
    }

    public function testExtractsRequestParametersFromForm()
    {
        $form = m::mock('Symfony\Component\DomCrawler\Form');

        $form->shouldReceive('getValues')->once()->andReturn([]);
        $this->assertEquals([], $this->extractParametersFromForm($form));

        $form->shouldReceive('getValues')->once()->andReturn(['name' => 'Laravel', 'license' => 'MIT']);
        $this->assertEquals(['name' => 'Laravel', 'license' => 'MIT'], $this->extractParametersFromForm($form));

        $form->shouldReceive('getValues')->once()->andReturn(['name' => 'Laravel', 'keywords[0]' => 'framework', 'keywords[1]' => 'laravel']);
        $this->assertEquals(['name' => 'Laravel', 'keywords' => ['framework', 'laravel']], $this->extractParametersFromForm($form));
    }
}
