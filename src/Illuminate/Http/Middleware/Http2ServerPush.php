<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class Http2ServerPush
{
    /**
     * The DomCrawler instance.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $limit = null)
    {
        $response = $next($request);

        if (! $response instanceof Response || $response->isRedirection() || $this->isJson($response)) {
            return $response;
        }

        $this->generateAndAttachLinkHeaders($response, $limit);

        return $response;
    }

    /**
     * @param \Illuminate\Http\Response $response
     *
     * @return $this
     */
    protected function generateAndAttachLinkHeaders(Response $response, $limit = null)
    {
        $headers = $this->fetchLinkableNodes($response)
            ->flatten(1)
            ->map(function ($url) {
                return $this->buildLinkHeaderString($url);
            })
            ->filter()
            ->take($limit)
            ->implode(',');

        if (! empty(trim($headers))) {
            $this->addLinkHeader($response, $headers);
        }

        return $this;
    }

    /**
     * Get the DomCrawler instance.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCrawler(Response $response)
    {
        if ($this->crawler) {
            return $this->crawler;
        }

        return $this->crawler = new Crawler($response->getContent());
    }

    /**
     * Get all nodes we are interested in pushing.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Illuminate\Support\Collection
     */
    protected function fetchLinkableNodes($response)
    {
        $crawler = $this->getCrawler($response);

        return collect($crawler->filter('link, script[src], img[src]')->extract(['src', 'href']));
    }

    /**
     * Build out header string based on asset extension.
     *
     * @param string $url
     *
     * @return string
     */
    private function buildLinkHeaderString($url)
    {
        $linkTypeMap = [
            '.CSS'  => 'style',
            '.JS'   => 'script',
            '.BMP'  => 'image',
            '.GIF'  => 'image',
            '.JPG'  => 'image',
            '.JPEG' => 'image',
            '.PNG'  => 'image',
            '.TIFF' => 'image',
        ];

        $type = collect($linkTypeMap)->first(function ($type, $extension) use ($url) {
            return str_contains(strtoupper($url), $extension);
        });

        return is_null($type) ? null : "<{$url}>; rel=preload; as={$type}";
    }

    /**
     * Add Link Header.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @param $link
     */
    private function addLinkHeader(Response $response, $link)
    {
        if ($response->headers->get('Link')) {
            $link = $response->headers->get('Link').','.$link;
        }

        $response->header('Link', $link);
    }

    private function isJson(Response $response)
    {
        return Str::contains($response->headers->get('Content-Type'), ['/json', '+json']);
    }
}
