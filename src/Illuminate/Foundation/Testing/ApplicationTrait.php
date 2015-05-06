<?php namespace Illuminate\Foundation\Testing;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;

trait ApplicationTrait
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The last response returned by the application.
     *
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * The last code returned by artisan cli.
     *
     * @var int
     */
    protected $code;

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
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        putenv('APP_ENV=testing');

        $this->app = $this->createApplication();
    }

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
     * @param  array  $heders
     * @return $this
     */
    public function get($uri, array $headers = array())
    {
        $this->call('GET', $uri, [], [], [], $headers);

        return $this;
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param  string  $uri
     * @param  array  $heders
     * @return $this
     */
    public function post($uri, array $data = array(), array $headers = array())
    {
        $this->call('POST', $uri, $data, [], [], $headers);

        return $this;
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * @param  string  $uri
     * @param  array  $heders
     * @return $this
     */
    public function put($uri, array $data = array(), array $headers = array())
    {
        $this->call('PUT', $uri, $data, [], [], $headers);

        return $this;
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * @param  string  $uri
     * @param  array  $heders
     * @return $this
     */
    public function patch($uri, array $data = array(), array $headers = array())
    {
        $this->call('PATCH', $uri, $data, [], [], $headers);

        return $this;
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * @param  string  $uri
     * @param  array  $heders
     * @return $this
     */
    public function delete($uri, array $data = array(), array $headers = array())
    {
        $this->call('DELETE', $uri, $data, [], [], $headers);

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

        $response = $this->call($method, $uri, $parameters, $cookies, $files);

        $this->clearInputs()->followRedirects()->assertPageLoaded($uri);

        $this->currentUri = $this->app->make('request')->fullUrl();

        $this->crawler = new Crawler($response->getContent(), $uri);

        return $this;
    }

    /**
     * Make a request to the application using the given form.
     *
     * @param  \Symfony\Component\DomCrawler\Form
     * @return $this
     */
    protected function makeRequestUsingForm(Form $form)
    {
        return $this->makeRequest(
            $form->getMethod(), $form->getUri(), $form->getValues(), [], $form->getFiles()
        );
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

        return $this;
    }

    /**
     * Assert that a given page successfully loaded.
     *
     * @param  string  $uri
     * @param  string  $message
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

        $this->$method("/".preg_quote($text, '/')."/i", $this->response->getContent());

        return $this;
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    protected function receiveJson(array $data = null)
    {
        $this->seeJson();

        if (! is_null($data)) {
            return $this->seeJson($data);
        }
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    public function seeJson(array $data = null)
    {
        if (is_null($data)) {
            $this->assertJson(
                $this->response->getContent(), "Failed asserting that JSON returned [{$this->currentUri}]."
            );

            return $this;
        }

        return $this->seeJsonContains($data);
    }

    /**
     * Assert that the response contains the given JSON.
     *
     * @param  array  $data
     * @return $this
     */
    protected function seeJsonContains(array $data)
    {
        $actual = json_encode(array_sort_recursive(
            json_decode($this->response->getContent(), true)
        ));

        foreach (array_sort_recursive($data) as $key => $value) {
            $expected = $this->formatToExpectedJson($key, $value);

            $this->assertTrue(
                str_contains($actual, $this->formatToExpectedJson($key, $value)),
                "Unable to find JSON fragment [{$expected}] within [{$actual}]."
            );
        }
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

        if (starts_with($expected, '{')) {
            $expected = substr($expected, 1);
        }

        if (ends_with($expected, '}')) {
            $expected = substr($expected, 0, -1);
        }

        return $expected;
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
     * Select an option from a drop-down.
     *
     * @param  string  $element
     * @param  string  $option
     * @return $this
     */
    protected function select($element, $option)
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
        return $this->submitForm($buttonText, $this->inputs);
    }

    /**
     * Submit a form on the page with the given input.
     *
     * @param  string  $buttonText
     * @param  array  $inputs
     * @return $this
     */
    protected function submitForm($buttonText, $inputs = [])
    {
        $this->makeRequestUsingForm($this->fillForm($buttonText, $inputs));

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
     * @param  string  $buttonText
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
     * @param  string $value
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

        return $this->crawler->filter("{$element}#{$name}, {$element}[name={$name}]");
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
        $this->currentUri = $this->prepareUrlForRequest($uri);

        $request = Request::create(
            $this->currentUri, $method, $parameters,
            $cookies, $files, $server, $content
        );

        return $this->response = $this->app->make('Illuminate\Contracts\Http\Kernel')->handle($request);
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
        if (starts_with($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (! starts_with($uri, 'http')) {
            $uri = $this->baseUrl.'/'.$uri;
        }

        return trim($uri, '/');
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
     * Set the session to the given array.
     *
     * @param  array  $data
     * @return void
     */
    public function session(array $data)
    {
        $this->startSession();

        foreach ($data as $key => $value) {
            $this->app['session']->put($key, $value);
        }
    }

    /**
     * Start the session for the application.
     *
     * @return void
     */
    protected function startSession()
    {
        if (! $this->app['session']->isStarted()) {
            $this->app['session']->start();
        }
    }

    /**
     * Flush all of the current session data.
     *
     * @return void
     */
    public function flushSession()
    {
        $this->startSession();

        $this->app['session']->flush();
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $driver
     * @return void
     */
    public function be(UserContract $user, $driver = null)
    {
        $this->app['auth']->driver($driver)->setUser($user);
    }

    /**
     * Seed a given database connection.
     *
     * @param  string  $class
     * @return void
     */
    public function seed($class = 'DatabaseSeeder')
    {
        $this->artisan('db:seed', ['--class' => $class]);
    }

    /**
     * Call artisan command and return code.
     *
     * @param string  $command
     * @param array   $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        return $this->code = $this->app['Illuminate\Contracts\Console\Kernel']->call($command, $parameters);
    }
}
