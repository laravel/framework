<?php

use Mockery as m;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class FoundationCrawlerTraitTest extends PHPUnit_Framework_TestCase
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
