<?php

use Mockery as m;
use Illuminate\Foundation\Testing\CrawlerTrait;

class FoundationCrawlerTraitTest extends PHPUnit_Framework_TestCase
{
    use CrawlerTrait;

    public function tearDown()
    {
        m::close();
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
