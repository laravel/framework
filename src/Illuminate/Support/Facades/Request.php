<?php

namespace Illuminate\Support\Facades;

/**
 * @method static capture() Create a new Illuminate HTTP request from server variables.
 * @method static $this instance() Return the Request instance.
 * @method static string method() Get the request method.
 * @method static string root() Get the root URL for the application.
 * @method static string url() Get the URL (no query string) for the request.
 * @method static string fullUrl() Get the full URL for the request.
 * @method static string fullUrlWithQuery(array $query) Get the full URL for the request with the added query string parameters.
 * @method static string path() Get the current path info for the request.
 * @method static string decodedPath() Get the current encoded path info for the request.
 * @method static string|null segment(int $index, string | null $default) Get a segment from the URI (1 based index).
 * @method static array segments() Get all of the segments for the request path.
 * @method static bool is(mixed $patterns) Determine if the current request URI matches a pattern.
 * @method static bool routeIs(mixed $patterns) Determine if the route name matches a given pattern.
 * @method static bool fullUrlIs(mixed $patterns) Determine if the current request URL and query string matches a pattern.
 * @method static bool ajax() Determine if the request is the result of an AJAX call.
 * @method static bool pjax() Determine if the request is the result of an PJAX call.
 * @method static bool secure() Determine if the request is over HTTPS.
 * @method static string ip() Get the client IP address.
 * @method static array ips() Get the client IP addresses.
 * @method static string userAgent() Get the client user agent.
 * @method static void merge(array $input) Merge new input into the current request's input array.
 * @method static void replace(array $input) Replace the input for the current request.
 * @method static mixed json(string $key, mixed $default) Get the JSON payload for the request.
 * @method static \Illuminate\Http\Request createFromBase(\Symfony\Component\HttpFoundation\Request $request) Create an Illuminate request from a Symfony instance.
 * @method static duplicate(array $query, array $request, array $attributes, array $cookies, array $files, array $server) Clones a request and overrides some of its parameters.
 * @method static \Illuminate\Session\Store session() Get the session associated with the request.
 * @method static void setLaravelSession(\Illuminate\Contracts\Session\Session $session) Set the session instance on the request.
 * @method static mixed user(string | null $guard) Get the user making the request.
 * @method static \Illuminate\Routing\Route|object|string route(string | null $param) Get the route handling the request.
 * @method static string fingerprint() Get a unique fingerprint for the request / route / IP address.
 * @method static $this setJson(array $json) Set the JSON payload for the request.
 * @method static \Closure getUserResolver() Get the user resolver callback.
 * @method static $this setUserResolver(\Closure $callback) Set the user resolver callback.
 * @method static \Closure getRouteResolver() Get the route resolver callback.
 * @method static $this setRouteResolver(\Closure $callback) Set the route resolver callback.
 * @method static array toArray() Get all of the input and files for the request.
 * @method static bool offsetExists(string $offset) Determine if the given offset exists.
 * @method static mixed offsetGet(string $offset) Get the value at the given offset.
 * @method static void offsetSet(string $offset, mixed $value) Set the value at the given offset.
 * @method static void offsetUnset(string $offset) Remove the value at the given offset.
 * @method static void initialize(array $query, array $request, array $attributes, array $cookies, array $files, array $server, string | resource $content) Sets the parameters for this request.
 * @method static createFromGlobals() Creates a new request with values from PHP's super globals.
 * @method static create(string $uri, string $method, array $parameters, array $cookies, array $files, array $server, string $content) Creates a Request based on a given URI and configuration.
 * @method static void setFactory() Sets a callable able to create a Request instance.
 * @method static void overrideGlobals() Overrides the PHP global variables according to this request instance.
 * @method static void setTrustedProxies(array $proxies, int $trustedHeaderSet) Sets a list of trusted proxies.
 * @method static array getTrustedProxies() Gets the list of trusted proxies.
 * @method static int getTrustedHeaderSet() Gets the set of trusted headers from trusted proxies.
 * @method static void setTrustedHosts(array $hostPatterns) Sets a list of trusted host patterns.
 * @method static array getTrustedHosts() Gets the list of trusted host patterns.
 * @method static void setTrustedHeaderName(string $key, string $value) Sets the name for trusted headers.
 * @method static string getTrustedHeaderName(string $key) Gets the trusted proxy header name.
 * @method static string normalizeQueryString(string $qs) Normalizes a query string.
 * @method static void enableHttpMethodParameterOverride() Enables support for the _method request parameter to determine the intended HTTP method.
 * @method static bool getHttpMethodParameterOverride() Checks whether support for the _method request parameter is enabled.
 * @method static mixed get(string $key, mixed $default) Gets a "parameter" value from any bag.
 * @method static \Symfony\Component\HttpFoundation\SessionInterface|null getSession() Gets the Session.
 * @method static bool hasPreviousSession() Whether the request contains a Session which was started in one of the
 * @method static bool hasSession() Whether the request contains a Session object.
 * @method static void setSession() Sets the Session.
 * @method static array getClientIps() Returns the client IP addresses.
 * @method static string|null getClientIp() Returns the client IP address.
 * @method static string getScriptName() Returns current script name.
 * @method static string getPathInfo() Returns the path being requested relative to the executed script.
 * @method static string getBasePath() Returns the root path from which this request is executed.
 * @method static string getBaseUrl() Returns the root URL from which this request is executed.
 * @method static string getScheme() Gets the request's scheme.
 * @method static int|string getPort() Returns the port on which the request is made.
 * @method static string|null getUser() Returns the user.
 * @method static string|null getPassword() Returns the password.
 * @method static string getUserInfo() Gets the user info.
 * @method static string getHttpHost() Returns the HTTP host being requested.
 * @method static string getRequestUri() Returns the requested URI (path and query string).
 * @method static string getSchemeAndHttpHost() Gets the scheme and HTTP host.
 * @method static string getUri() Generates a normalized URI (URL) for the Request.
 * @method static string getUriForPath(string $path) Generates a normalized URI for the given path.
 * @method static string getRelativeUriForPath(string $path) Returns the path as relative reference from the current Request path.
 * @method static string|null getQueryString() Generates the normalized query string for the Request.
 * @method static bool isSecure() Checks whether the request is secure or not.
 * @method static string getHost() Returns the host name.
 * @method static void setMethod(string $method) Sets the request method.
 * @method static string getMethod() Gets the request "intended" method.
 * @method static string getRealMethod() Gets the "real" request method.
 * @method static string getMimeType(string $format) Gets the mime type associated with the format.
 * @method static array getMimeTypes(string $format) Gets the mime types associated with the format.
 * @method static string|null getFormat(string $mimeType) Gets the format associated with the mime type.
 * @method static void setFormat(string $format, string | array $mimeTypes) Associates a format with mime types.
 * @method static string getRequestFormat(string $default) Gets the request format.
 * @method static void setRequestFormat(string $format) Sets the request format.
 * @method static string|null  getContentType() Gets the format associated with the request.
 * @method static void setDefaultLocale(string $locale) Sets the default locale.
 * @method static string getDefaultLocale() Get the default locale.
 * @method static void setLocale(string $locale) Sets the locale.
 * @method static string getLocale() Get the locale.
 * @method static bool isMethod(string $method) Checks if the request method is of specified type.
 * @method static bool isMethodSafe(bool $andCacheable) Checks whether or not the method is safe.
 * @method static bool isMethodIdempotent() Checks whether or not the method is idempotent.
 * @method static bool isMethodCacheable() Checks whether the method is cacheable or not.
 * @method static string|resource getContent(bool $asResource) Returns the request body content.
 * @method static array getETags() Gets the Etags.
 * @method static bool isNoCache()
 * @method static string|null getPreferredLanguage(array $locales) Returns the preferred language.
 * @method static array getLanguages() Gets a list of languages acceptable by the client browser.
 * @method static array getCharsets() Gets a list of charsets acceptable by the client browser.
 * @method static array getEncodings() Gets a list of encodings acceptable by the client browser.
 * @method static array getAcceptableContentTypes() Gets a list of content types acceptable by the client browser.
 * @method static bool isXmlHttpRequest() Returns true if the request is a XMLHttpRequest.
 * @method static bool isFromTrustedProxy() Indicates whether this request originated from a trusted proxy.
 * @method static bool matchesType(string $actual, string $type) Determine if the given content types match.
 * @method static bool isJson() Determine if the request is sending JSON.
 * @method static bool expectsJson() Determine if the current request probably expects a JSON response.
 * @method static bool wantsJson() Determine if the current request is asking for JSON in return .
 * @method static bool accepts(string | array $contentTypes) Determines whether the current requests accepts a given content type.
 * @method static string|null prefers(string | array $contentTypes) Return the most suitable content type from the given array based on content negotiation.
 * @method static bool acceptsJson() Determines whether a request accepts JSON.
 * @method static bool acceptsHtml() Determines whether a request accepts HTML.
 * @method static string format(string $default) Get the data format expected in the response.
 * @method static string|array old(string $key, string | array | null $default) Retrieve an old input item.
 * @method static void flash() Flash the input for the current request to the session.
 * @method static void flashOnly(array | mixed $keys) Flash only some of the input to the session.
 * @method static void flashExcept(array | mixed $keys) Flash only some of the input to the session.
 * @method static void flush() Flush all of the old input from the session.
 * @method static string|array server(string $key, string | array | null $default) Retrieve a server variable from the request.
 * @method static bool hasHeader(string $key) Determine if a header is set on the request.
 * @method static string|array header(string $key, string | array | null $default) Retrieve a header from the request.
 * @method static string|null bearerToken() Get the bearer token from the request headers.
 * @method static bool exists(string | array $key) Determine if the request contains a given input item key.
 * @method static bool has(string | array $key) Determine if the request contains a given input item key.
 * @method static bool hasAny(mixed $key) Determine if the request contains any of the given inputs.
 * @method static bool filled(string | array $key) Determine if the request contains a non-empty value for an input item.
 * @method static array keys() Get the keys for all of the input and files.
 * @method static array all(array | mixed $keys) Get all of the input and files for the request.
 * @method static string|array input(string $key, string | array | null $default) Retrieve an input item from the request.
 * @method static array only(array | mixed $keys) Get a subset containing the provided keys with values from the input data.
 * @method static array except(array | mixed $keys) Get all of the input except for a specified array of items.
 * @method static string|array query(string $key, string | array | null $default) Retrieve a query string item from the request.
 * @method static string|array post(string $key, string | array | null $default) Retrieve a request payload item from the request.
 * @method static bool hasCookie(string $key) Determine if a cookie is set on the request.
 * @method static string|array cookie(string $key, string | array | null $default) Retrieve a cookie from the request.
 * @method static array allFiles() Get an array of all of the files on the request.
 * @method static bool hasFile(string $key) Determine if the uploaded data contains a file.
 * @method static \Illuminate\Http\UploadedFile|array|null file(string $key, mixed $default) Retrieve a file from the request.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 * @method static void validate()
 *
 * @see \Illuminate\Http\Request
 */
class Request extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'request';
    }
}
