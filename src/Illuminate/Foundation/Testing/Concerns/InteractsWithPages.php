<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Foundation\Testing\HttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;

trait InteractsWithPages
{
    /**
     * The DomCrawler instance.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * All of the stored inputs for the current page.
     *
     * @var array
     */
    protected $inputs = [];

    /**
     * All of the stored uploads for the current page.
     *
     * @var array
     */
    protected $uploads = [];

    /**
     * Visit the given URI with a GET request.
     *
     * @param  string  $uri
     * @return $this
     */
    public function visit($uri)
    {
        return $this->makeRequest('GET', $uri);
    }

    /**
     * Make a request to the application and create a Crawler instance.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @return $this
     */
    protected function makeRequest($method, $uri, $parameters = [], $cookies = [], $files = [])
    {
        $uri = $this->prepareUrlForRequest($uri);

        $this->call($method, $uri, $parameters, $cookies, $files);

        $this->clearInputs()->followRedirects()->assertPageLoaded($uri);

        $this->currentUri = $this->app->make('request')->fullUrl();

        $this->crawler = new Crawler($this->response->getContent(), $uri);

        return $this;
    }

    /**
     * Make a request to the application using the given form.
     *
     * @param  \Symfony\Component\DomCrawler\Form  $form
     * @param  array  $uploads
     * @return $this
     */
    protected function makeRequestUsingForm(Form $form, array $uploads = [])
    {
        $files = $this->convertUploadsForTesting($form, $uploads);

        return $this->makeRequest(
            $form->getMethod(), $form->getUri(), $this->extractParametersFromForm($form), [], $files
        );
    }

    /**
     * Extract the parameters from the given form.
     *
     * @param  \Symfony\Component\DomCrawler\Form  $form
     * @return array
     */
    protected function extractParametersFromForm(Form $form)
    {
        parse_str(http_build_query($form->getValues()), $parameters);

        return $parameters;
    }

    /**
     * Follow redirects from the last response.
     *
     * @return $this
     */
    protected function followRedirects()
    {
        while ($this->response->isRedirect()) {
            $this->makeRequest('GET', $this->response->getTargetUrl());
        }

        return $this;
    }

    /**
     * Clear the inputs for the current page.
     *
     * @return $this
     */
    protected function clearInputs()
    {
        $this->inputs = [];

        $this->uploads = [];

        return $this;
    }

    /**
     * Assert that the current page matches a given URI.
     *
     * @param  string  $uri
     * @return $this
     */
    protected function seePageIs($uri)
    {
        $this->assertPageLoaded($uri = $this->prepareUrlForRequest($uri));

        $this->assertEquals(
            $uri, $this->currentUri, "Did not land on expected page [{$uri}].\n"
        );

        return $this;
    }

    /**
     * Assert that a given page successfully loaded.
     *
     * @param  string  $uri
     * @param  string|null  $message
     * @return void
     */
    protected function assertPageLoaded($uri, $message = null)
    {
        $status = $this->response->getStatusCode();

        try {
            $this->assertEquals(200, $status);
        } catch (PHPUnitException $e) {
            $message = $message ?: "A request to [{$uri}] failed. Received status code [{$status}].";

            $responseException = isset($this->response->exception)
                    ? $this->response->exception : null;

            throw new HttpException($message, null, $responseException);
        }
    }

    /**
     * Assert that a given string is seen on the page.
     *
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    protected function see($text, $negate = false)
    {
        $method = $negate ? 'assertNotRegExp' : 'assertRegExp';

        $rawPattern = preg_quote($text, '/');

        $escapedPattern = preg_quote(e($text), '/');

        $pattern = $rawPattern == $escapedPattern
                ? $rawPattern : "({$rawPattern}|{$escapedPattern})";

        $this->$method("/$pattern/i", $this->response->getContent());

        return $this;
    }

    /**
     * Assert that a given string is not seen on the page.
     *
     * @param  string  $text
     * @return $this
     */
    protected function dontSee($text)
    {
        return $this->see($text, true);
    }

    /**
     * Assert that a given string is seen inside an element.
     *
     * @param  bool|string|null  $element
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    protected function seeInElement($element, $text, $negate = false)
    {
        $method = $negate ? 'assertNotRegExp' : 'assertRegExp';

        $rawPattern = preg_quote($text, '/');

        $escapedPattern = preg_quote(e($text), '/');

        $content = $this->crawler->filter($element)->html();

        $pattern = $rawPattern == $escapedPattern
                ? $rawPattern : "({$rawPattern}|{$escapedPattern})";

        $this->$method("/$pattern/i", $content);

        return $this;
    }

    /**
     * Assert that a given string is not seen inside an element.
     *
     * @param  string  $text
     * @param  string|null  $element
     * @return $this
     */
    protected function dontSeeInElement($element, $text)
    {
        return $this->seeInElement($element, $text, true);
    }

    /**
     * Assert that a given link is seen on the page.
     *
     * @param  string  $text
     * @param  string|null  $url
     * @return $this
     */
    public function seeLink($text, $url = null)
    {
        $message = "No links were found with expected text [{$text}]";

        if ($url) {
            $message .= " and URL [{$url}]";
        }

        $this->assertTrue($this->hasLink($text, $url), "{$message}.");

        return $this;
    }

    /**
     * Assert that a given link is not seen on the page.
     *
     * @param  string  $text
     * @param  string|null  $url
     * @return $this
     */
    public function dontSeeLink($text, $url = null)
    {
        $message = "A link was found with expected text [{$text}]";

        if ($url) {
            $message .= " and URL [{$url}]";
        }

        $this->assertFalse($this->hasLink($text, $url), "{$message}.");

        return $this;
    }

    /**
     * Check if the page has a link with the given $text and optional $url.
     *
     * @param  string  $text
     * @param  string|null  $url
     * @return bool
     */
    protected function hasLink($text, $url = null)
    {
        $links = $this->crawler->selectLink($text);

        if ($links->count() == 0) {
            return false;
        }

        // If the URL is null, we assume the developer only wants to find a link
        // with the given text regardless of the URL. So, if we find the link
        // we will return true now. Otherwise, we look for the given URL.
        if ($url == null) {
            return true;
        }

        $url = $this->addRootToRelativeUrl($url);

        foreach ($links as $link) {
            if ($link->getAttribute('href') == $url) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a root if the URL is relative (helper method of the hasLink function).
     *
     * @param  string  $url
     * @return string
     */
    protected function addRootToRelativeUrl($url)
    {
        if (! Str::startsWith($url, ['http', 'https'])) {
            return $this->app->make('url')->to($url);
        }

        return $url;
    }

    /**
     * Assert that an input field contains the given value.
     *
     * @param  string  $selector
     * @param  string  $expected
     * @return $this
     */
    public function seeInField($selector, $expected)
    {
        $this->assertSame(
            $expected, $this->getInputOrTextAreaValue($selector),
            "The field [{$selector}] does not contain the expected value [{$expected}]."
        );

        return $this;
    }

    /**
     * Assert that an input field does not contain the given value.
     *
     * @param  string  $selector
     * @param  string  $value
     * @return $this
     */
    public function dontSeeInField($selector, $value)
    {
        $this->assertNotSame(
            $this->getInputOrTextAreaValue($selector), $value,
            "The input [{$selector}] should not contain the value [{$value}]."
        );

        return $this;
    }

    /**
     * Assert that the given checkbox is selected.
     *
     * @param  string  $selector
     * @return $this
     */
    public function seeIsChecked($selector)
    {
        $this->assertTrue(
            $this->isChecked($selector),
            "The checkbox [{$selector}] is not checked."
        );

        return $this;
    }

    /**
     * Assert that the given checkbox is not selected.
     *
     * @param  string  $selector
     * @return $this
     */
    public function dontSeeIsChecked($selector)
    {
        $this->assertFalse(
            $this->isChecked($selector),
            "The checkbox [{$selector}] is checked."
        );

        return $this;
    }

    /**
     * Assert that the expected value is selected.
     *
     * @param  string  $selector
     * @param  string  $expected
     * @return $this
     */
    public function seeIsSelected($selector, $expected)
    {
        $this->assertEquals(
            $expected, $this->getSelectedValue($selector),
            "The field [{$selector}] does not contain the selected value [{$expected}]."
        );

        return $this;
    }

    /**
     * Assert that the given value is not selected.
     *
     * @param  string  $selector
     * @param  string  $value
     * @return $this
     */
    public function dontSeeIsSelected($selector, $value)
    {
        $this->assertNotEquals(
            $value, $this->getSelectedValue($selector),
            "The field [{$selector}] contains the selected value [{$value}]."
        );

        return $this;
    }

    /**
     * Get the value of an input or textarea.
     *
     * @param  string  $selector
     * @return string
     *
     * @throws \Exception
     */
    protected function getInputOrTextAreaValue($selector)
    {
        $field = $this->filterByNameOrId($selector, ['input', 'textarea']);

        if ($field->count() == 0) {
            throw new Exception("There are no elements with the name or ID [$selector].");
        }

        $element = $field->nodeName();

        if ($element == 'input') {
            return $field->attr('value');
        }

        if ($element == 'textarea') {
            return $field->text();
        }

        throw new Exception("Given selector [$selector] is not an input or textarea.");
    }

    /**
     * Get the selected value of a select field or radio group.
     *
     * @param  string  $selector
     * @return string|null
     *
     * @throws \Exception
     */
    protected function getSelectedValue($selector)
    {
        $field = $this->filterByNameOrId($selector);

        if ($field->count() == 0) {
            throw new Exception("There are no elements with the name or ID [$selector].");
        }

        $element = $field->nodeName();

        if ($element == 'select') {
            return $this->getSelectedValueFromSelect($field);
        }

        if ($element == 'input') {
            return $this->getCheckedValueFromRadioGroup($field);
        }

        throw new Exception("Given selector [$selector] is not a select or radio group.");
    }

    /**
     * Get the selected value from a select field.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $field
     * @return string|null
     *
     * @throws \Exception
     */
    protected function getSelectedValueFromSelect(Crawler $field)
    {
        if ($field->nodeName() !== 'select') {
            throw new Exception('Given element is not a select element.');
        }

        foreach ($field->children() as $option) {
            if ($option->hasAttribute('selected')) {
                return $option->getAttribute('value');
            }
        }

        return;
    }

    /**
     * Get the checked value from a radio group.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $radioGroup
     * @return string|null
     *
     * @throws \Exception
     */
    protected function getCheckedValueFromRadioGroup(Crawler $radioGroup)
    {
        if ($radioGroup->nodeName() !== 'input' || $radioGroup->attr('type') !== 'radio') {
            throw new Exception('Given element is not a radio button.');
        }

        foreach ($radioGroup as $radio) {
            if ($radio->hasAttribute('checked')) {
                return $radio->getAttribute('value');
            }
        }

        return;
    }

    /**
     * Return true if the given checkbox is checked, false otherwise.
     *
     * @param  string  $selector
     * @return bool
     *
     * @throws \Exception
     */
    protected function isChecked($selector)
    {
        $checkbox = $this->filterByNameOrId($selector, "input[type='checkbox']");

        if ($checkbox->count() == 0) {
            throw new Exception("There are no checkbox elements with the name or ID [$selector].");
        }

        return $checkbox->attr('checked') !== null;
    }

    /**
     * Click a link with the given body, name, or ID attribute.
     *
     * @param  string  $name
     * @return $this
     */
    protected function click($name)
    {
        $link = $this->crawler->selectLink($name);

        if (! count($link)) {
            $link = $this->filterByNameOrId($name, 'a');

            if (! count($link)) {
                throw new InvalidArgumentException(
                    "Could not find a link with a body, name, or ID attribute of [{$name}]."
                );
            }
        }

        $this->visit($link->link()->getUri());

        return $this;
    }

    /**
     * Fill an input field with the given text.
     *
     * @param  string  $text
     * @param  string  $element
     * @return $this
     */
    protected function type($text, $element)
    {
        return $this->storeInput($element, $text);
    }

    /**
     * Check a checkbox on the page.
     *
     * @param  string  $element
     * @return $this
     */
    protected function check($element)
    {
        return $this->storeInput($element, true);
    }

    /**
     * Uncheck a checkbox on the page.
     *
     * @param  string  $element
     * @return $this
     */
    protected function uncheck($element)
    {
        return $this->storeInput($element, false);
    }

    /**
     * Select an option from a drop-down.
     *
     * @param  string  $option
     * @param  string  $element
     * @return $this
     */
    protected function select($option, $element)
    {
        return $this->storeInput($element, $option);
    }

    /**
     * Attach a file to a form field on the page.
     *
     * @param  string  $absolutePath
     * @param  string  $element
     * @return $this
     */
    protected function attach($absolutePath, $element)
    {
        $this->uploads[$element] = $absolutePath;

        return $this->storeInput($element, $absolutePath);
    }

    /**
     * Submit a form using the button with the given text value.
     *
     * @param  string  $buttonText
     * @return $this
     */
    protected function press($buttonText)
    {
        return $this->submitForm($buttonText, $this->inputs, $this->uploads);
    }

    /**
     * Submit a form on the page with the given input.
     *
     * @param  string  $buttonText
     * @param  array  $inputs
     * @param  array  $uploads
     * @return $this
     */
    protected function submitForm($buttonText, $inputs = [], $uploads = [])
    {
        $this->makeRequestUsingForm($this->fillForm($buttonText, $inputs), $uploads);

        return $this;
    }

    /**
     * Fill the form with the given data.
     *
     * @param  string  $buttonText
     * @param  array  $inputs
     * @return \Symfony\Component\DomCrawler\Form
     */
    protected function fillForm($buttonText, $inputs = [])
    {
        if (! is_string($buttonText)) {
            $inputs = $buttonText;

            $buttonText = null;
        }

        return $this->getForm($buttonText)->setValues($inputs);
    }

    /**
     * Get the form from the page with the given submit button text.
     *
     * @param  string|null  $buttonText
     * @return \Symfony\Component\DomCrawler\Form
     */
    protected function getForm($buttonText = null)
    {
        try {
            if ($buttonText) {
                return $this->crawler->selectButton($buttonText)->form();
            }

            return $this->crawler->filter('form')->form();
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Could not find a form that has submit button [{$buttonText}]."
            );
        }
    }

    /**
     * Store a form input in the local array.
     *
     * @param  string  $element
     * @param  string  $text
     * @return $this
     */
    protected function storeInput($element, $text)
    {
        $this->assertFilterProducesResults($element);

        $element = str_replace('#', '', $element);

        $this->inputs[$element] = $text;

        return $this;
    }

    /**
     * Assert that a filtered Crawler returns nodes.
     *
     * @param  string  $filter
     * @return void
     */
    protected function assertFilterProducesResults($filter)
    {
        $crawler = $this->filterByNameOrId($filter);

        if (! count($crawler)) {
            throw new InvalidArgumentException(
                "Nothing matched the filter [{$filter}] CSS query provided for [{$this->currentUri}]."
            );
        }
    }

    /**
     * Filter elements according to the given name or ID attribute.
     *
     * @param  string  $name
     * @param  array|string  $elements
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function filterByNameOrId($name, $elements = '*')
    {
        $name = str_replace('#', '', $name);

        $id = str_replace(['[', ']'], ['\\[', '\\]'], $name);

        $elements = is_array($elements) ? $elements : [$elements];

        array_walk($elements, function (&$element) use ($name, $id) {
            $element = "{$element}#{$id}, {$element}[name='{$name}']";
        });

        return $this->crawler->filter(implode(', ', $elements));
    }

    /**
     * Convert the given uploads to UploadedFile instances.
     *
     * @param  \Symfony\Component\DomCrawler\Form  $form
     * @param  array  $uploads
     * @return array
     */
    protected function convertUploadsForTesting(Form $form, array $uploads)
    {
        $files = $form->getFiles();

        $names = array_keys($files);

        $files = array_map(function (array $file, $name) use ($uploads) {
            return isset($uploads[$name])
                        ? $this->getUploadedFileForTesting($file, $uploads, $name)
                        : $file;
        }, $files, $names);

        return array_combine($names, $files);
    }

    /**
     * Create an UploadedFile instance for testing.
     *
     * @param  array  $file
     * @param  array  $uploads
     * @param  string  $name
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected function getUploadedFileForTesting($file, $uploads, $name)
    {
        return new UploadedFile(
            $file['tmp_name'], basename($uploads[$name]), $file['type'], $file['size'], $file['error'], true
        );
    }
}
