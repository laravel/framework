<?php

namespace Illuminate\Routing;

use BackedEnum;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RouteUrlGenerator
{
    /**
     * The URL generator instance.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The named parameter defaults.
     *
     * @var array
     */
    public $defaultParameters = [];

    /**
     * Characters that should not be URL encoded.
     *
     * @var array
     */
    public $dontEncode = [
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
        '%3B' => ';',
        '%2C' => ',',
        '%3D' => '=',
        '%2B' => '+',
        '%21' => '!',
        '%2A' => '*',
        '%7C' => '|',
        '%3F' => '?',
        '%26' => '&',
        '%23' => '#',
        '%25' => '%',
    ];

    /**
     * Create a new Route URL generator.
     *
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($url, $request)
    {
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * Generate a URL for the given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    public function to($route, $parameters = [], $absolute = false)
    {
        $parameters = $this->formatParameters($route, $parameters);
        $domain = $this->getRouteDomain($route, $parameters);

        // First we will construct the entire URI including the root and query string. Once it
        // has been constructed, we'll make sure we don't have any missing parameters or we
        // will need to throw the exception to let the developers know one was not given.
        $uri = $this->addQueryString($this->url->format(
            $root = $this->replaceRootParameters($route, $domain, $parameters),
            $this->replaceRouteParameters($route->uri(), $parameters),
            $route
        ), $parameters);

        if (preg_match_all('/{(.*?)}/', $uri, $matchedMissingParameters)) {
            throw UrlGenerationException::forMissingParameters($route, $matchedMissingParameters[1]);
        }

        // Once we have ensured that there are no missing parameters in the URI we will encode
        // the URI and prepare it for returning to the developer. If the URI is supposed to
        // be absolute, we will return it as-is. Otherwise we will remove the URL's root.
        $uri = strtr(rawurlencode($uri), $this->dontEncode);

        if (! $absolute) {
            $uri = preg_replace('#^(//|[^/?])+#', '', $uri);

            if ($base = $this->request->getBaseUrl()) {
                $uri = preg_replace('#^'.$base.'#i', '', $uri);
            }

            return '/'.ltrim($uri, '/');
        }

        return $uri;
    }

    // note(wip) this is now used when formatting parameters for URLs
    // *to routes* (matches the purpose of this class) vs the formatParameters
    // method in UrlGenerator that is used for url()->to()
    //
    // the goal of this method is to match every *provided parameter*
    // to every *route parameter*, making parameters significantly
    // less ambiguous and easier to handle in other parts of code
    //
    // extra parameters (query strings) are included in the produced array
    // just like before and the final call to $this->url->formatParameters()
    // handles turning model instances & similar into route keys even for
    // query strings -- just like before
    protected function formatParameters(Route $route, $parameters): array
    {
        // Passed parameters
        $parameters = Arr::wrap($parameters);

        // todo(wip) all count($passedParameters) comparisons
        // also work with count($parameters), there should be
        // a test case added that makes it clear which one
        // should be used
        $passedParameters = $parameters;

        // Parameters that have a string key
        $namedParameters = [];

        // Passed parameters that have a string key but are not route parameters
        $namedQueryParameters = [];

        // Names of parameters in the route definition that don't have
        // a default value in $this->defaultParameters
        $routeParametersWithoutDefaults = [];

        $routeParameters = $route->parameterNames();

        foreach ($routeParameters as $name) {
            if (isset($parameters[$name])) {
                // Named parameters don't need any special handling
                $namedParameters[$name] = $parameters[$name];
                unset($parameters[$name]);

                continue;
            } else if (! isset($this->defaultParameters[$name])) {
                // If there's no named parameter AND no default value
                // we track this parameter as a parameter without a
                // default value and try to match positional parameters
                // to it in the code below.
                array_push($routeParametersWithoutDefaults, $name);
            }

            // Empty value to be filled later or ignored
            $namedParameters[$name] = '';
        }

        // Named parameters that don't have an associated route parameter
        // will be used for query strings and therefore shouldn't
        // be used in the following logic for matching provided
        // parameters with route parameters. They're only
        // transformed at the end of the function.
        foreach ($parameters as $key => $value) {
            if (is_string($key)) {
                $namedQueryParameters[$key] = $value;
                unset($parameters[$key]);
            }
        }

        // todo(wip) remove, just for debugging convenience.
        // by this point we want to be sure we're working
        // with a regular indexed array, so our array_pop() calls
        // will not mess up anything related to named parameters
        foreach (array_keys($parameters) as $k) {
            assert(! is_string($k));
        }

        // note(wip) if the passed parameter count matches the count of
        // parameters without defaults, we use the last passed parameter
        // for the last route parameter without a default value and so on
        // (handling cases where e..g a parameter with a default follows
        // one without a default)
        //
        // if the passed count is lower, we already know we'll run into a
        // "missing required parameter" situation. (note: if we used
        // count($parameters) here instead the check WOULD have to be <=
        // because we unset *named* parameters above)
        //
        // if the passed count is higher, we skip this step
        if (count($passedParameters) == count($routeParametersWithoutDefaults)) {
            foreach (array_reverse($routeParametersWithoutDefaults) as $name) {
                if (count($parameters) === 0) {
                    break;
                }

                $namedParameters[$name] = array_pop($parameters);
            }
        }

        // If the number of provided parameters is equal
        // to the number of total parameters, the user
        // specified all positional parameters.
        //
        // If the number is greater, the user specified
        // additonal query parameters.
        //
        // In either of these cases, we don't have to do
        // the reverse matching below.
        //
        // Defaults also play no role here -- if the user
        // provided an equal or greater than number of
        // parameters than required, but still intends
        // to use some defaults, with the named query parameters
        // being for the query string, we don't have any way
        // of recognizing that intent.
        //
        // We still make use of the other logic however,
        // since parameters formatted into an actual key-value
        // pair are more accurately handled by the rest of the
        // routing system -- namely, *other* places that insert
        // defaults would override positional arguments.
        $reverse = count($passedParameters) < count($routeParameters);

        foreach ($reverse ? array_reverse($namedParameters) : $namedParameters as $key => $value) {
            $bindingField = $route->bindingFieldFor($key);
            $defaultParameterKey = $bindingField ? "$key:$bindingField" : $key;

            if ($value !== '') {
                continue;
            } else if (! empty($parameters)) {
                $namedParameters[$key] = $reverse ? array_pop($parameters) : array_shift($parameters);
            } else if (isset($this->defaultParameters[$defaultParameterKey])) {
                $namedParameters[$key] = $this->defaultParameters[$defaultParameterKey];
            }
        }

        // note(wip) here we could unset $namedParameters items with '' values
        // but replaceNamedParameters() should be ignoring those just fine

        // note(wip) any remaining values in $parameters by this point are
        // (unnamed) query string params rather than route params.

        $parameters = array_merge($namedParameters, $namedQueryParameters, $parameters);

        $parameters = collect($parameters)->map(function ($value, $key) use ($route) {
            return $value instanceof UrlRoutable && $route->bindingFieldFor($key)
                    ? $value->{$route->bindingFieldFor($key)}
                    : $value;
        })->all();

        array_walk_recursive($parameters, function (&$item) {
            if ($item instanceof BackedEnum) {
                $item = $item->value;
            }
        });

        return $this->url->formatParameters($parameters);
    }

    /**
     * Get the formatted domain for a given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteDomain($route, &$parameters)
    {
        return $route->getDomain() ? $this->formatDomain($route, $parameters) : null;
    }

    /**
     * Format the domain and port for the route and request.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return string
     */
    protected function formatDomain($route, &$parameters)
    {
        return $this->addPortToDomain(
            $this->getRouteScheme($route).$route->getDomain()
        );
    }

    /**
     * Get the scheme for the given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getRouteScheme($route)
    {
        if ($route->httpOnly()) {
            return 'http://';
        } elseif ($route->httpsOnly()) {
            return 'https://';
        }

        return $this->url->formatScheme();
    }

    /**
     * Add the port to the domain if necessary.
     *
     * @param  string  $domain
     * @return string
     */
    protected function addPortToDomain($domain)
    {
        $secure = $this->request->isSecure();

        $port = (int) $this->request->getPort();

        return ($secure && $port === 443) || (! $secure && $port === 80)
                    ? $domain : $domain.':'.$port;
    }

    /**
     * Replace the parameters on the root path.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $domain
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRootParameters($route, $domain, &$parameters)
    {
        $scheme = $this->getRouteScheme($route);

        return $this->replaceRouteParameters(
            $this->url->formatRoot($scheme, $domain), $parameters
        );
    }

    /**
     * Replace all of the wildcard parameters for a route path.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRouteParameters($path, array &$parameters)
    {
        $path = $this->replaceNamedParameters($path, $parameters);

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            // Reset only the numeric keys...
            $parameters = array_merge($parameters);

            return (! isset($parameters[0]) && ! str_ends_with($match[0], '?}'))
                        ? $match[0]
                        : Arr::pull($parameters, 0);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    /**
     * Replace all of the named parameters in the path.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceNamedParameters($path, &$parameters)
    {
        return preg_replace_callback('/\{(.*?)(\?)?\}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]]) && $parameters[$m[1]] !== '') {
                return Arr::pull($parameters, $m[1]);
            } elseif (isset($this->defaultParameters[$m[1]])) {
                return $this->defaultParameters[$m[1]];
            } elseif (isset($parameters[$m[1]])) {
                Arr::pull($parameters, $m[1]);
            }

            return $m[0];
        }, $path);
    }

    /**
     * Add a query string to the URI.
     *
     * @param  string  $uri
     * @param  array  $parameters
     * @return mixed|string
     */
    protected function addQueryString($uri, array $parameters)
    {
        // If the URI has a fragment we will move it to the end of this URI since it will
        // need to come after any query string that may be added to the URL else it is
        // not going to be available. We will remove it then append it back on here.
        if (! is_null($fragment = parse_url($uri, PHP_URL_FRAGMENT))) {
            $uri = preg_replace('/#.*/', '', $uri);
        }

        $uri .= $this->getRouteQueryString($parameters);

        return is_null($fragment) ? $uri : $uri."#{$fragment}";
    }

    /**
     * Get the query string for a given route.
     *
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteQueryString(array $parameters)
    {
        // First we will get all of the string parameters that are remaining after we
        // have replaced the route wildcards. We'll then build a query string from
        // these string parameters then use it as a starting point for the rest.
        if (count($parameters) === 0) {
            return '';
        }

        $query = Arr::query(
            $keyed = $this->getStringParameters($parameters)
        );

        // Lastly, if there are still parameters remaining, we will fetch the numeric
        // parameters that are in the array and add them to the query string or we
        // will make the initial query string if it wasn't started with strings.
        if (count($keyed) < count($parameters)) {
            $query .= '&'.implode(
                '&', $this->getNumericParameters($parameters)
            );
        }

        $query = trim($query, '&');

        return $query === '' ? '' : "?{$query}";
    }

    /**
     * Get the string parameters from a given list.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getStringParameters(array $parameters)
    {
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the numeric parameters from a given list.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getNumericParameters(array $parameters)
    {
        return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Set the default named parameters used by the URL generator.
     *
     * @param  array  $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        $this->defaultParameters = array_merge(
            $this->defaultParameters, $defaults
        );
    }
}
