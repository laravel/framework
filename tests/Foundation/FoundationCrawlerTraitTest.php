<?php

use Mockery as m;
use Illuminate\Foundation\Testing\CrawlerTrait;
use Symfony\Component\DomCrawler\Crawler;

class FoundationCrawlerTraitTest extends PHPUnit_Framework_TestCase
{
    use CrawlerTrait;

    public function tearDown()
    {
        m::close();
    }

    public function testSeeInFieldInput()
    {
        $input = m::mock(Crawler::class)->makePartial();
        $input->shouldReceive('count')->andReturn(1);
        $input->shouldReceive('nodeName')->once()->andReturn('input');
        $input->shouldReceive('attr')
            ->withArgs(['value'])
            ->once()
            ->andReturn('Laravel');

        $this->crawler = m::mock(Crawler::class)->makePartial();

        $this->crawler->shouldReceive('filter')
            ->withArgs(["*#framework, *[name='framework']"])
            ->once()
            ->andReturn($input);

        $this->seeInField('framework', 'Laravel');
    }

    public function testSeeInFieldTextarea()
    {
        $textarea = m::mock(Crawler::class)->makePartial();
        $textarea->shouldReceive('count')->andReturn(1);
        $textarea->shouldReceive('nodeName')->once()->andReturn('textarea');
        $textarea->shouldReceive('text')->once()->andReturn('Laravel is awesome');

        $this->crawler = m::mock(Crawler::class)->makePartial();

        $this->crawler->shouldReceive('filter')
            ->withArgs(["*#description, *[name='description']"])
            ->once()
            ->andReturn($textarea);

        $this->seeInField('description', 'Laravel is awesome');
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

        $this->crawler = m::mock(Crawler::class)->makePartial();

        $this->crawler->shouldReceive('filter')
            ->withArgs(["*#select, *[name='select']"])
            ->once()
            ->andReturn($select);

        $this->seeInField('select', 'selected_value');
    }

    public function testSeeIsSelected()
    {
        $optionEmpty = m::mock(Crawler::class)->makePartial();
        $optionEmpty->shouldReceive('hasAttribute')
            ->withArgs(['selected'])
            ->once()
            ->andReturn(false);

        $optionFullTime = m::mock(Crawler::class)->makePartial();
        $optionFullTime->shouldReceive('hasAttribute')
            ->withArgs(['selected'])
            ->once()
            ->andReturn(true);
        $optionFullTime->shouldReceive('getAttribute')
            ->withArgs(['value'])
            ->once()
            ->andReturn('full_time');

        $select = m::mock(Crawler::class)->makePartial();
        $select->shouldReceive('count')
            ->once()
            ->andReturn(1);
        $select->shouldReceive('nodeName')
            ->twice()
            ->andReturn('select');
        $select->shouldReceive('children')
            ->once()
            ->andReturn([$optionEmpty, $optionFullTime]);

        $this->crawler = m::mock(Crawler::class)->makePartial();

        $this->crawler->shouldReceive('filter')
            ->withArgs(["*#availability, *[name='availability']"])
            ->once()
            ->andReturn($select);

        $this->seeIsSelected('availability', 'full_time');
    }

    public function testSeeIsChecked()
    {
        $checkbox = m::mock(Crawler::class)->makePartial();
        $checkbox->shouldReceive('count')->andReturn(1);
        $checkbox->shouldReceive('attr')
            ->withArgs(['checked'])
            ->once()
            ->andReturn('checked');

        $this->crawler = m::mock(Crawler::class)->makePartial();

        $this->crawler->shouldReceive('filter')
            ->withArgs(["input[type='checkbox']#terms, input[type='checkbox'][name='terms']"])
            ->once()
            ->andReturn($checkbox);

        $this->seeIsChecked('terms');
    }

    public function testExtractsRequestParametersFromForm()
    {
        $form = m::mock('\Symfony\Component\DomCrawler\Form');

        $form->shouldReceive('getValues')->once()->andReturn([]);
        $this->assertEquals([], $this->extractParametersFromForm($form));

        $form->shouldReceive('getValues')->once()->andReturn(['name' => 'Laravel', 'license' => 'MIT']);
        $this->assertEquals(['name' => 'Laravel', 'license' => 'MIT'], $this->extractParametersFromForm($form));

        $form->shouldReceive('getValues')->once()->andReturn(['name' => 'Laravel', 'keywords[0]' => 'framework', 'keywords[1]' => 'laravel']);
        $this->assertEquals(['name' => 'Laravel', 'keywords' => ['framework', 'laravel']], $this->extractParametersFromForm($form));
    }
}
