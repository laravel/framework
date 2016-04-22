<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use InvalidArgumentException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Foundation\Testing\HttpException;
use Illuminate\Foundation\Testing\Constraints\HasText;
use Illuminate\Foundation\Testing\Constraints\HasLink;
use Illuminate\Foundation\Testing\Constraints\HasValue;
use Illuminate\Foundation\Testing\Constraints\HasSource;
use Illuminate\Foundation\Testing\Constraints\IsChecked;
use Illuminate\Foundation\Testing\Constraints\HasElement;
use Illuminate\Foundation\Testing\Constraints\IsSelected;
use Illuminate\Foundation\Testing\Constraints\HasInElement;
use Illuminate\Foundation\Testing\Constraints\PageConstraint;
use Illuminate\Foundation\Testing\Constraints\ReversePageConstraint;
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
     * Nested crawler instances used by the "within" method.
     *
     * @var array
     */
    protected $subCrawlers = [];

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

        $this->crawler = new Crawler($this->response->getContent(), $this->currentUri);

        return $this;
    }

    /**
     * Clean the crawler and the subcrawlers values to reset the page context.
     *
     * @return void
     */
    protected function resetPageContext()
    {
        $this->crawler = null;

        $this->subCrawlers = [];
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
     *
     * @throws \Illuminate\Foundation\Testing\HttpException
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
     * Narrow the test content to a specific area of the page.
     *
     * @param  string  $element
     * @param  \Closure  $callback
     * @return $this
     */
    public function within($element, Closure $callback)
    {
        $this->subCrawlers[] = $this->crawler()->filter($element);

        $callback();

        array_pop($this->subCrawlers);

        return $this;
    }

    /**
     * Get the current crawler according to the test context.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function crawler()
    {
        if (! empty($this->subCrawlers)) {
            return end($this->subCrawlers);
        }

        return $this->crawler;
    }

    /**
     * Assert the given constraint.
     *
     * @param  \Illuminate\Foundation\Testing\Constraints\PageConstraint  $constraint
     * @param  bool  $reverse
     * @param  string  $message
     * @return $this
     */
    protected function assertInPage(PageConstraint $constraint, $reverse = false, $message = '')
    {
        if ($reverse) {
            $constraint = new ReversePageConstraint($constraint);
        }

        self::assertThat(
            $this->crawler() ?: $this->response->getContent(),
            $constraint, $message
        );

        return $this;
    }

    /**
     * Assert that a given string is seen on the current HTML.
     *
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function see($text, $negate = false)
    {
        return $this->assertInPage(new HasSource($text), $negate);
    }

    /**
     * Assert that a given string is not seen on the current HTML.
     *
     * @param  string  $text
     * @return $this
     */
    public function dontSee($text)
    {
        return $this->assertInPage(new HasSource($text), true);
    }

    /**
     * Assert that an element is present on the page.
     *
     * @param  string  $selector
     * @param  array  $attributes
     * @param  bool  $negate
     * @return $this
     */
    public function seeElement($selector, array $attributes = [], $negate = false)
    {
        return $this->assertInPage(new HasElement($selector, $attributes), $negate);
    }

    /**
     * Assert that an element is not present on the page.
     *
     * @param  string  $selector
     * @param  array  $attributes
     * @return $this
     */
    public function dontSeeElement($selector, array $attributes = [])
    {
        return $this->assertInPage(new HasElement($selector, $attributes), true);
    }

    /**
     * Assert that a given string is seen on the current text.
     *
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function seeText($text, $negate = false)
    {
        return $this->assertInPage(new HasText($text), $negate);
    }

    /**
     * Assert that a given string is not seen on the current text.
     *
     * @param  string  $text
     * @return $this
     */
    public function dontSeeText($text)
    {
        return $this->assertInPage(new HasText($text), true);
    }

    /**
     * Assert that a given string is seen inside an element.
     *
     * @param  string  $element
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function seeInElement($element, $text, $negate = false)
    {
        return $this->assertInPage(new HasInElement($element, $text), $negate);
    }

    /**
     * Assert that a given string is not seen inside an element.
     *
     * @param  string  $element
     * @param  string  $text
     * @return $this
     */
    public function dontSeeInElement($element, $text)
    {
        return $this->assertInPage(new HasInElement($element, $text), true);
    }

    /**
     * Assert that a given link is seen on the page.
     *
     * @param  string $text
     * @param  string|null $url
     * @param  bool  $negate
     * @return $this
     */
    public function seeLink($text, $url = null, $negate = false)
    {
        return $this->assertInPage(new HasLink($text, $url), $negate);
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
        return $this->assertInPage(new HasLink($text, $url), true);
    }

    /**
     * Assert that an input field contains the given value.
     *
     * @param  string  $selector
     * @param  string  $expected
     * @param  bool  $negate
     * @return $this
     */
    public function seeInField($selector, $expected, $negate = false)
    {
        return $this->assertInPage(new HasValue($selector, $expected), $negate);
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
        return $this->assertInPage(new HasValue($selector, $value), true);
    }

    /**
     * Assert that the expected value is selected.
     *
     * @param  string  $selector
     * @param  string  $value
     * @param  bool  $negate
     * @return $this
     */
    public function seeIsSelected($selector, $value, $negate = false)
    {
        return $this->assertInPage(new IsSelected($selector, $value), $negate);
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
        return $this->assertInPage(new IsSelected($selector, $value), true);
    }

    /**
     * Assert that the given checkbox is selected.
     *
     * @param  string  $selector
     * @param  bool  $negate
     * @return $this
     */
    public function seeIsChecked($selector, $negate = false)
    {
        return $this->assertInPage(new IsChecked($selector), $negate);
    }

    /**
     * Assert that the given checkbox is not selected.
     *
     * @param  string  $selector
     * @return $this
     */
    public function dontSeeIsChecked($selector)
    {
        return $this->assertInPage(new IsChecked($selector), true);
    }

    /**
     * Click a link with the given body, name, or ID attribute.
     *
     * @param  string  $name
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    protected function click($name)
    {
        $link = $this->crawler()->selectLink($name);

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
     *
     * @throws \InvalidArgumentException
     */
    protected function getForm($buttonText = null)
    {
        try {
            if ($buttonText) {
                return $this->crawler()->selectButton($buttonText)->form();
            }

            return $this->crawler()->filter('form')->form();
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
     *
     * @throws \InvalidArgumentException
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

        return $this->crawler()->filter(implode(', ', $elements));
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
     * @return \Illuminate\Http\UploadedFile
     */
    protected function getUploadedFileForTesting($file, $uploads, $name)
    {
        return new UploadedFile(
            $file['tmp_name'], basename($uploads[$name]), $file['type'], $file['size'], $file['error'], true
        );
    }
}
