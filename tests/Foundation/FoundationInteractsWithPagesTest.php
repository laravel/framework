<?php

use Mockery as m;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class FoundationInteractsWithPagesTest extends PHPUnit_Framework_TestCase
{
    use MakesHttpRequests;

    protected $baseUrl = 'https://laravel.com';

    protected function setCrawler($html)
    {
        $this->crawler = new Crawler($html);
    }

    public function testSeePageIs()
    {
        $this->currentUri = 'https://laravel.com/docs';

        $this->response = m::mock(Response::class);
        $this->response->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200);

        $this->seePageIs('/docs');
    }

    public function testSeeThroughWebCrawler()
    {
        $this->setCrawler('<p>The PHP Framework For Web Artisans</p>');
        $this->see('Web Artisans');
    }

    public function testSeeThroughResponse()
    {
        $this->crawler = null;

        $this->response = m::mock(Response::class);
        $this->response->shouldReceive('getContent')
            ->once()
            ->andReturn('<p>The PHP Framework For Web Artisans</p>');

        $this->see('Web Artisans');
    }

    public function testDontSee()
    {
        $this->setCrawler('<p>The PHP Framework For Web Artisans</p>');
        $this->dontSee('Webmasters');
    }

    public function testSeeTextAndDontSeeText()
    {
        $this->setCrawler('<p>Laravel is a <strong>PHP Framework</strong>.');

        // The methods see and dontSee compare against the HTML.
        $this->see('strong');
        $this->dontSee('Laravel is a PHP Framework');

        // seeText and dontSeeText strip the HTML and compare against the plain text.
        $this->seeText('Laravel is a PHP Framework.');
        $this->dontSeeText('strong');
    }

    public function testSeeInElement()
    {
        $this->setCrawler('<div>Laravel was created by <strong>Taylor Otwell</strong></div>');
        $this->seeInElement('strong', 'Taylor');
    }

    public function testSeeInElementSearchInAllElements()
    {
        $this->setCrawler(
            '<div>
                Laravel is a <strong>PHP framework</strong>
                created by <strong>Taylor Otwell</strong>
            </div>'
        );

        $this->seeInElement('strong', 'Taylor');
    }

    public function testSeeInElementSearchInHtmlTags()
    {
        $this->setCrawler(
            '<div id="mytable">
                <img src="image.jpg">
            </div>'
        );

        $this->seeInElement('#mytable', 'image.jpg');
    }

    public function testdontSeeInElement()
    {
        $this->setCrawler(
            '<div>Laravel was created by <strong>Taylor Otwell</strong></div>'
        );

        $this->seeInElement('strong', 'Laravel', true);
        $this->dontSeeInElement('strong', 'Laravel');
    }

    public function testSeeLink()
    {
        $this->setCrawler(
            '<a href="https://laravel.com">Laravel</a>'
        );

        $this->seeLink('Laravel');
        $this->seeLink('Laravel', 'https://laravel.com');
    }

    public function testDontSeeLink()
    {
        $this->setCrawler(
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
        $this->setCrawler($this->getInputHtml());
        $this->seeInField('framework', 'Laravel');
    }

    public function testDontSeeInInput()
    {
        $this->setCrawler($this->getInputHtml());
        $this->dontSeeInField('framework', 'Rails');
    }

    protected function getInputArrayHtml()
    {
        return '<input type="text" name="framework[]" value="Laravel">';
    }

    public function testSeeInInputArray()
    {
        $this->setCrawler($this->getInputArrayHtml());
        $this->seeInField('framework[]', 'Laravel');
    }

    public function testDontSeeInInputArray()
    {
        $this->setCrawler($this->getInputArrayHtml());
        $this->dontSeeInField('framework[]', 'Rails');
    }

    protected function getTextareaHtml()
    {
        return '<textarea name="description">Laravel is awesome</textarea>';
    }

    public function testSeeInTextarea()
    {
        $this->setCrawler($this->getTextareaHtml());
        $this->seeInField('description', 'Laravel is awesome');
    }

    public function testDontSeeInTextarea()
    {
        $this->setCrawler($this->getTextareaHtml());
        $this->dontSeeInField('description', 'Rails is awesome');
    }

    protected function getSelectHtml()
    {
        return
         '<select name="availability">
              <option value="partial_time">Partial time</option>
              <option value="full_time" selected>Full time</option>
          </select>';
    }

    public function testSeeOptionIsSelected()
    {
        $this->setCrawler($this->getSelectHtml());
        $this->seeIsSelected('availability', 'full_time');
    }

    public function testDontSeeOptionIsSelected()
    {
        $this->setCrawler($this->getSelectHtml());
        $this->dontSeeIsSelected('availability', 'partial_time');
    }

    protected function getMultipleSelectHtml()
    {
        return
         '<select name="roles[]">
              <option value="admin">Administrator</option>
              <option value="user" selected>User</option>
              <option value="journalist">Journalist</option>
              <option value="reviewer" selected>Reviewer</option>
          </select>';
    }

    public function testSeeMultipleOptionsAreSelected()
    {
        $this->setCrawler($this->getMultipleSelectHtml());
        $this->seeIsSelected('roles[]', 'user');
        $this->seeIsSelected('roles[]', 'reviewer');
    }

    public function testDontSeeMultipleOptionsAreSelected()
    {
        $this->setCrawler($this->getMultipleSelectHtml());
        $this->dontSeeIsSelected('roles[]', 'admin');
        $this->dontSeeIsSelected('roles[]', 'journalist');
    }

    protected function getSelectWithOptGroupHtml()
    {
        return
            '<select name="technology">
                <optgroup label="PHP">
                    <option value="laravel" selected>Laravel</option>
                    <option value="symfony">Symfony</option>
                </optgroup>
                <optgroup label="JavaScript">
                    <option value="angular">AngularJS</option>
                    <option value="vue">Vue.js</option>
                </optgroup>
            </select>';
    }

    public function testSeeOptionInOptgroupIsSelected()
    {
        $this->setCrawler($this->getSelectWithOptGroupHtml());
        $this->seeIsSelected('technology', 'laravel');
    }

    public function testDontseeOptionInOptgroupIsSelected()
    {
        $this->setCrawler($this->getSelectWithOptGroupHtml());
        $this->dontSeeIsSelected('technology', 'symfony');
    }

    protected function getSelectMultipleWithOptGroupHtml()
    {
        return
            '<select name="technologies[]">
                <optgroup label="PHP">
                    <option value="laravel" selected>Laravel</option>
                    <option value="symfony">Symfony</option>
                </optgroup>
                <optgroup label="JavaScript">
                    <option value="angular">AngularJS</option>
                    <option value="vue" selected>Vue.js</option>
                </optgroup>
            </select>';
    }

    public function testSeeOptionsInOptgroupAreSelected()
    {
        $this->setCrawler($this->getSelectMultipleWithOptGroupHtml());
        $this->seeIsSelected('technologies[]', 'laravel');
        $this->seeIsSelected('technologies[]', 'vue');
    }

    public function testDontseeOptionsInOptgroupAreSelected()
    {
        $this->setCrawler($this->getSelectMultipleWithOptGroupHtml());
        $this->dontSeeIsSelected('technologies[]', 'symfony');
        $this->dontSeeIsSelected('technologies[]', 'angular');
    }

    protected function getRadiosHtml()
    {
        return
         '<input type="radio" name="availability" value="partial_time">'
        .'<input type="radio" name="availability" value="full_time" checked>';
    }

    public function testSeeRadioIsChecked()
    {
        $this->setCrawler($this->getRadiosHtml());
        $this->seeIsSelected('availability', 'full_time');
    }

    public function testDontSeeRadioIsChecked()
    {
        $this->setCrawler($this->getRadiosHtml());
        $this->dontSeeIsSelected('availability', 'partial_time');
    }

    protected function getCheckboxesHtml()
    {
        return
             '<input type="checkbox" name="terms" checked>
              <input type="checkbox" name="list">';
    }

    public function testSeeCheckboxIsChecked()
    {
        $this->setCrawler($this->getCheckboxesHtml());
        $this->seeIsChecked('terms');
    }

    public function testDontSeeCheckboxIsChecked()
    {
        $this->setCrawler($this->getCheckboxesHtml());
        $this->dontSeeIsChecked('list');
    }

    public function testSeeElement()
    {
        $this->setCrawler('<image>');
        $this->seeElement('image');
    }

    public function testSeeElementWithAttributes()
    {
        $this->setCrawler('<image width="100" height="50">');
        $this->seeElement('image', ['width' => 100, 'height' => 50]);

        $this->setCrawler('<select><option value="laravel" selected>Laravel</option>');
        $this->seeElement('option', ['value' => 'laravel', 'selected']);

        $this->setCrawler('<input name="name" id="name" type="text" required>');
        $this->seeElement('#name', ['required']);
    }

    public function testDontSeeElement()
    {
        $this->setCrawler('<image class="img">');
        $this->dontSeeElement('iframe');
        $this->dontSeeElement('image', ['id']);
        $this->dontSeeElement('image', ['class' => 'video']);

        $this->setCrawler('<input type="text">');
        $this->dontSeeElement('textarea');
        $this->dontSeeElement('input', ['required']);
    }

    protected function getLayoutHtml()
    {
        return
            '<header>
                <h1>Laravel</h1>
            </header>
            <section id="features">
	            <h2>The PHP Framework For Web Artisans</h2>
	            <p>Elegant applications delivered at warp speed.</p>
            </section>
            <footer>
                <a href="docs">Documentation</a>
            </footer>';
    }

    public function testWithin()
    {
        $this->setCrawler($this->getLayoutHtml());

        // Limit the search to the "header" area
        $this->within('header', function () {
            $this->see('Laravel')
                 ->dontSeeInElement('h2', 'PHP Framework');
        });

        // Make sure we are out of the within context
        $this->seeLink('Documentation');

        // Test other methods as well
        $this->within('#features', function () {
            $this->seeInElement('h2', 'PHP Framework')
                ->dontSee('Laravel')
                ->dontSeeLink('Documentation');
        });
    }

    public function testNestedWithin()
    {
        $this->setCrawler($this->getLayoutHtml());

        $this->within('#features', function () {
            $this->dontSee('Laravel')
                ->see('Web Artisans')
                ->within('h2', function () {
                    $this->see('PHP Framework')
                        ->dontSee('Elegant applications');
                });
        });
    }
}
