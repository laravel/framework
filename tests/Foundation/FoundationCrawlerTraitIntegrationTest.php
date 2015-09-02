<?php

use Illuminate\Foundation\Testing\CrawlerTrait;
use Symfony\Component\DomCrawler\Crawler;

class FoundationCrawlerTraitIntegrationTest extends PHPUnit_Framework_TestCase
{
    use CrawlerTrait;

    public function testSeeInInput()
    {
        $this->crawler = new Crawler(
            '<input type="text" name="framework" value="Laravel">'
        );

        $this->seeInField('framework', 'Laravel');
    }

    public function testSeeInInputArray()
    {
        $this->crawler = new Crawler(
            '<input type="text" name="framework[]" value="Laravel">'
        );

        $this->seeInField('framework[]', 'Laravel');
    }

    public function testSeeInTextarea()
    {
        $this->crawler = new Crawler(
            '<textarea name="description">Laravel is awesome</textarea>'
        );

        $this->seeInField('description', 'Laravel is awesome');
    }

    public function testSeeOptionIsSelected()
    {
        $this->crawler = new Crawler(
            ' <select name="availability">'
            .'    <option value="partial_time">Partial time</option>'
            .'    <option value="full_time" selected>Full time</option>'
            .'</select>'
        );

        $this->seeIsSelected('availability', 'full_time');
    }

    public function testSeeRadioIsChecked()
    {
        $this->crawler = new Crawler(
            ' <input type="radio" name="availability" value="partial_time">'
            .'<input type="radio" name="availability" value="full_time" checked>'
        );

        $this->seeIsSelected('availability', 'full_time');
    }

    public function testSeeCheckboxIsChecked()
    {
        $this->crawler = new Crawler(
            '<input type="checkbox" name="terms" checked>'
        );

        $this->seeIsChecked('terms');
    }
}
