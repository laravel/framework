<?php

namespace Illuminate\Foundation\Testing;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;

trait CrawlerTrait
{
    /**
     * The last response returned by the application.
     *
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * The DomCrawler instance.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * The current URL being viewed.
     *
     * @var string
     */
    protected $currentUri;

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
     * Additional server variables for the request.
     *
     * @var array
     */
    protected $serverVariables = [];

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
     * Visit the given URI with a GET request.
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return $this
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('GET', $uri, [], [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('POST', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('PUT', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('PATCH', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('DELETE', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Send the given request through the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function handle(Request $request)
    {
        $this->currentUri = $request->fullUrl();

        $this->response = $this->app->prepareResponse($this->app->handle($request));

        return $this;
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

            throw new PHPUnitException($message, null, $this->response->exception);
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

        $this->$method("/({$rawPattern}|{$escapedPattern})/i", $this->response->getContent());

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
        $field = $this->filterByNameOrId($selector);

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
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    protected function shouldReturnJson(array $data = null)
    {
        return $this->receiveJson($data);
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    protected function receiveJson($data = null)
    {
        $this->seeJson();

        if (! is_null($data)) {
            return $this->seeJson($data);
        }
    }

    /**
     * Assert that the response contains an exact JSON array.
     *
     * @param  array  $data
     * @return $this
     */
    public function seeJsonEquals(array $data)
    {
        $actual = json_encode(array_sort_recursive(
            json_decode($this->response->getContent(), true)
        ));

        $this->assertEquals(json_encode(array_sort_recursive($data)), $actual);

        return $this;
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @param  bool  $negate
     * @return $this
     */
    public function seeJson(array $data = null, $negate = false)
    {
        if (is_null($data)) {
            $this->assertJson(
                $this->response->getContent(), "Failed asserting that JSON returned [{$this->currentUri}]."
            );

            return $this;
        }

        return $this->seeJsonContains($data, $negate);
    }

    /**
     * Assert that the response doesn't contain JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    public function dontSeeJson(array $data = null)
    {
        return $this->seeJson($data, true);
    }

    /**
     * Assert that the response contains the given JSON.
     *
     * @param  array  $data
     * @param  bool  $negate
     * @return $this
     */
    protected function seeJsonContains(array $data, $negate = false)
    {
        $method = $negate ? 'assertFalse' : 'assertTrue';

        $actual = json_encode(array_sort_recursive(
            json_decode($this->response->getContent(), true)
        ));

        foreach (array_sort_recursive($data) as $key => $value) {
            $expected = $this->formatToExpectedJson($key, $value);

            $this->{$method}(
                Str::contains($actual, $expected),
                ($negate ? 'Found unexpected' : 'Unable to find')." JSON fragment [{$expected}] within [{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Format the given key and value into a JSON string for expectation checks.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function formatToExpectedJson($key, $value)
    {
        $expected = json_encode([$key => $value]);

        if (Str::startsWith($expected, '{')) {
            $expected = substr($expected, 1);
        }

        if (Str::endsWith($expected, '}')) {
            $expected = substr($expected, 0, -1);
        }

        return $expected;
    }

    /**
     * Asserts that the status code of the response matches the given code.
     *
     * @param  int  $status
     * @return $this
     */
    protected function seeStatusCode($status)
    {
        $this->assertEquals($status, $this->response->getStatusCode());

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
        return $this->landOn($uri);
    }

    /**
     * Assert that the current page matches a given URI.
     *
     * @param  string  $uri
     * @return $this
     */
    protected function onPage($uri)
    {
        return $this->landOn($uri);
    }

    /**
     * Assert that the current page matches a given URI.
     *
     * @param  string  $uri
     * @return $this
     */
    protected function landOn($uri)
    {
        $this->assertPageLoaded($uri = $this->prepareUrlForRequest($uri));

        $this->assertEquals(
            $uri, $this->currentUri, "Did not land on expected page [{$uri}].\n"
        );

        return $this;
    }

    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * @param  string $headerName
     * @param  mixed $value
     * @return $this
     */
    protected function seeHeader($headerName, $value = null)
    {
        $headers = $this->response->headers;

        $this->assertTrue($headers->has($headerName), "Header [{$headerName}] not present on response.");

        if (! is_null($value)) {
            $this->assertEquals(
                $headers->get($headerName), $value,
                "Header [{$headerName}] was found, but value [{$headers->get($headerName)}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * @param  string $cookieName
     * @param  mixed $value
     * @return $this
     */
    protected function seeCookie($cookieName, $value = null)
    {
        $headers = $this->response->headers;

        $exist = false;

        foreach ($headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $exist = true;
                break;
            }
        }

        $this->assertTrue($exist, "Cookie [{$cookieName}] not present on response.");

        if (! is_null($value)) {
            $this->assertEquals(
                $cookie->getValue(), $value,
                "Cookie [{$cookieName}] was found, but value [{$cookie->getValue()}] does not match [{$value}]."
            );
        }

        return $this;
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
     * @param  string  $element
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function filterByNameOrId($name, $element = '*')
    {
        $name = str_replace('#', '', $name);

        return $this->crawler->filter("{$element}#{$name}, {$element}[name='{$name}']");
    }

    /**
     * Define a set of server variables to be sent with the requests.
     *
     * @param  array  $server
     * @return $this
     */
    protected function withServerVariables(array $server)
    {
        $this->serverVariables = $server;

        return $this;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        $this->currentUri = $this->prepareUrlForRequest($uri);

        $request = Request::create(
            $this->currentUri, $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );

        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        return $this->response = $response;
    }

    /**
     * Call the given HTTPS URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function callSecure($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->app['url']->secure(ltrim($uri, '/'));

        return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Call a controller action and return the Response.
     *
     * @param  string  $method
     * @param  string  $action
     * @param  array   $wildcards
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function action($method, $action, $wildcards = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->app['url']->action($action, $wildcards, true);

        return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Call a named route and return the Response.
     *
     * @param  string  $method
     * @param  string  $name
     * @param  array   $routeParameters
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function route($method, $name, $routeParameters = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->app['url']->route($name, $routeParameters);

        return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Turn the given URI into a fully qualified URL.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (! Str::startsWith($uri, 'http')) {
            $uri = $this->baseUrl.'/'.$uri;
        }

        return trim($uri, '/');
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (! starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }

            $server[$name] = $value;
        }

        return $server;
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

    /**
     * Disable middleware for the test.
     *
     * @return $this
     */
    public function withoutMiddleware()
    {
        $this->app->instance('middleware.disable', true);

        return $this;
    }

    /**
     * Dump the content from the last response.
     *
     * @return void
     */
    public function dump()
    {
        $content = $this->response->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        dd($content);
    }
}
