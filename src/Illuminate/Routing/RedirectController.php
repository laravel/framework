<?php

namespace Illuminate\Routing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RedirectController extends Controller
{
    /**
     * Invoke the controller method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request, UrlGenerator $url)
    {
        $parameters = collect($request->route()->parameters());

        $status = $parameters->get('status');

        $destination = $parameters->get('destination');

        if($parameters->get('preserve') && $queryParameters = $request->query->all()) {
            $components = parse_url(preg_replace('/\{.*?\?\}/', '', $destination));
            
            if (isset($components['query'])) {
                parse_str(html_entity_decode($components['query']), $qs);

                $query = array_replace($qs, $queryParameters);
                $queryString = http_build_query($query, '', '&');
            } else {
                $queryString = http_build_query($queryParameters, '', '&');
            }

            $destination = $destination .'?'.$queryString;
        }

        $parameters->forget('status')->forget('destination')->forget('preserve');

        $route = (new Route('GET', $destination, [
            'as' => 'laravel_route_redirect_destination',
        ]))->bind($request);

        $parameters = $parameters->only(
            $route->getCompiled()->getPathVariables()
        )->all();

        $url = $url->toRoute($route, $parameters, false);

        if (! str_starts_with($destination, '/') && str_starts_with($url, '/')) {
            $url = Str::after($url, '/');
        }

        return new RedirectResponse($url, $status);
    }
}
