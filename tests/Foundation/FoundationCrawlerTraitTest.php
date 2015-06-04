<?php

use Mockery as m;
use Illuminate\Foundation\Testing\CrawlerTrait;

class FoundationCrawlerTraitTest extends PHPUnit_Framework_TestCase
{
    use CrawlerTrait;

    public function test_making_request_parameters_using_form()
    {
        $form = m::mock('\Symfony\Component\DomCrawler\Form');

        $form->shouldReceive('getValues')->once()->andReturn([]);
        $this->assertEquals([], $this->makeRequestParametersUsingForm($form));

        $form->shouldReceive('getValues')->once()->andReturn(['name' => 'Laravel', 'license' => 'MIT']);
        $this->assertEquals(['name' => 'Laravel', 'license' => 'MIT'], $this->makeRequestParametersUsingForm($form));

        $form->shouldReceive('getValues')->once()->andReturn(['name' => 'Laravel', 'keywords[0]' => 'framework', 'keywords[1]' => 'laravel']);
        $this->assertEquals(['name' => 'Laravel', 'keywords' => ['framework', 'laravel']], $this->makeRequestParametersUsingForm($form));
    }
}
