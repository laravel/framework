<?php namespace Illuminate\Foundation\Testing;

use Illuminate\Auth\UserInterface;

trait ApplicationTrait {

    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The HttpKernel client instance.
     *
     * @var \Illuminate\Foundation\Testing\Client
     */
    protected $client;

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();

        $this->client = $this->createClient();

        $this->app->setRequestForConsoleEnvironment();

        $this->app->boot();
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @param  bool    $changeHistory
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)
    {
        $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        return $this->client->getResponse();
    }

    /**
     * Call the given HTTPS URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @param  bool    $changeHistory
     * @return \Illuminate\Http\Response
     */
    public function callSecure($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)
    {
        $uri = 'https://localhost/'.ltrim($uri, '/');

        return $this->call($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    /**
     * Call a controller action and return the Response.
     *
     * @param  string  $method
     * @param  string  $action
     * @param  array   $wildcards
     * @param  array   $parameters
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @param  bool    $changeHistory
     * @return \Illuminate\Http\Response
     */
    public function action($method, $action, $wildcards = array(), $parameters = array(), $files = array(), $server = array(), $content = null, $changeHistory = true)
    {
        $uri = $this->app['url']->action($action, $wildcards, true);

        return $this->call($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    /**
     * Call a named route and return the Response.
     *
     * @param  string  $method
     * @param  string  $name
     * @param  array   $routeParameters
     * @param  array   $parameters
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @param  bool    $changeHistory
     * @return \Illuminate\Http\Response
     */
    public function route($method, $name, $routeParameters = array(), $parameters = array(), $files = array(), $server = array(), $content = null, $changeHistory = true)
    {
        $uri = $this->app['url']->route($name, $routeParameters);

        return $this->call($method, $uri, $parameters, $files, $server, $content, $changeHistory);
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

        foreach ($data as $key => $value)
        {
            $this->app['session']->put($key, $value);
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
     * Start the session for the application.
     *
     * @return void
     */
    protected function startSession()
    {
        if ( ! $this->app['session']->isStarted())
        {
            $this->app['session']->start();
        }
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @param  \Illuminate\Auth\UserInterface  $user
     * @param  string  $driver
     * @return void
     */
    public function be(UserInterface $user, $driver = null)
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
        $this->app['artisan']->call('db:seed', array('--class' => $class));
    }

    /**
     * Create a new HttpKernel client instance.
     *
     * @param  array  $server
     * @return \Symfony\Component\HttpKernel\Client
     */
    protected function createClient(array $server = array())
    {
        return new Client($this->app, $server);
    }

}
