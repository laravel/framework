<?php

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class FoundationCrawlerTraitIntegrationTest extends PHPUnit_Framework_TestCase
{
    use MakesHttpRequests;

    public function testSeeInElement()
    {
        $this->crawler = new Crawler(
            '<div>Laravel was created by <strong>Taylor Otwell</strong></div>'
        );

        $this->seeInElement('strong', 'Taylor');
    }

    public function testSeeInElementSearchInAllElements()
    {
        $this->crawler = new Crawler(
            '<div>
                Laravel is a <strong>PHP framework</strong>
                created by <strong>Taylor Otwell</strong>
            </div>'
        );

        $this->seeInElement('strong', 'Taylor');
    }

    public function testdontSeeInElement()
    {
        $this->crawler = new Crawler(
            '<div>Laravel was created by <strong>Taylor Otwell</strong></div>'
        );

        $this->seeInElement('strong', 'Laravel', true);
        $this->dontSeeInElement('strong', 'Laravel');
    }

    public function testSeeLink()
    {
        $this->crawler = new Crawler(
            '<a href="https://laravel.com">Laravel</a>'
        );

        $this->seeLink('Laravel');
        $this->seeLink('Laravel', 'https://laravel.com');
    }

    public function testDontSeeLink()
    {
        $this->crawler = new Crawler(
            '<a href="https://laravel.com">Laravel</a>'
        );

        $this->dontSeeLink('Symfony');
        $this->dontSeeLink('Symfony', 'https://symfonyc.com');
    }

    protected function getInputHtml()
    {
        return '<input type="text" name="framework" value="Laravel">';
    }

    public function testSeeInInput()
    {
        $this->crawler = new Crawler($this->getInputHtml());
        $this->seeInField('framework', 'Laravel');
    }

    public function testDontSeeInInput()
    {
        $this->crawler = new Crawler($this->getInputHtml());
        $this->dontSeeInField('framework', 'Rails');
    }

    protected function getInputArrayHtml()
    {
        return '<input type="text" name="framework[]" value="Laravel">';
    }

    public function testSeeInInputArray()
    {
        $this->crawler = new Crawler($this->getInputArrayHtml());
        $this->seeInField('framework[]', 'Laravel');
    }

    public function testDontSeeInInputArray()
    {
        $this->crawler = new Crawler($this->getInputArrayHtml());
        $this->dontSeeInField('framework[]', 'Rails');
    }

    protected function getTextareaHtml()
    {
        return '<textarea name="description">Laravel is awesome</textarea>';
    }

    public function testSeeInTextarea()
    {
        $this->crawler = new Crawler($this->getTextareaHtml());
        $this->seeInField('description', 'Laravel is awesome');
    }

    public function testDontSeeInTextarea()
    {
        $this->crawler = new Crawler($this->getTextareaHtml());
        $this->dontSeeInField('description', 'Rails is awesome');
    }

    protected function getSelectHtml()
    {
        return
         '<select name="availability">'
        .'    <option value="partial_time">Partial time</option>'
        .'    <option value="full_time" selected>Full time</option>'
        .'</select>';
    }

    public function testSeeOptionIsSelected()
    {
        $this->crawler = new Crawler($this->getSelectHtml());
        $this->seeIsSelected('availability', 'full_time');
    }

    public function testDontSeeOptionIsSelected()
    {
        $this->crawler = new Crawler($this->getSelectHtml());
        $this->dontSeeIsSelected('availability', 'partial_time');
    }

    protected function getRadiosHtml()
    {
        return
         '<input type="radio" name="availability" value="partial_time">'
        .'<input type="radio" name="availability" value="full_time" checked>';
    }

    public function testSeeRadioIsChecked()
    {
        $this->crawler = new Crawler($this->getRadiosHtml());
        $this->seeIsSelected('availability', 'full_time');
    }

    public function testDontSeeRadioIsChecked()
    {
        $this->crawler = new Crawler($this->getRadiosHtml());
        $this->dontSeeIsSelected('availability', 'partial_time');
    }

    protected function getCheckboxesHtml()
    {
        return
             '<input type="checkbox" name="terms" checked>'
            .'<input type="checkbox" name="list">';
    }

    public function testSeeCheckboxIsChecked()
    {
        $this->crawler = new Crawler($this->getCheckboxesHtml());
        $this->seeIsChecked('terms');
    }

    public function testDontSeeCheckboxIsChecked()
    {
        $this->crawler = new Crawler($this->getCheckboxesHtml());
        $this->dontSeeIsChecked('list');
    }
}
