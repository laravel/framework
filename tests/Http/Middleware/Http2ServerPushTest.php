<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\Middleware\Http2ServerPush as Push;

class AddHttp2ServerPushTest extends TestCase
{
    public function setUp()
    {
        $this->middleware = new Push();
    }

    /** @test */
    public function it_will_not_modify_a_response_with_no_server_push_assets()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithoutAssets'));

        $this->assertFalse($this->isServerPushResponse($response));
    }

    /** @test */
    public function it_will_not_modify_a_json_response()
    {
        $request = new Request();

        $next = $this->getNext('pageWithCss');

        $response = $this->middleware->handle($request, function ($request) use ($next) {
            $response = $next($request);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        });

        $this->assertFalse($this->isServerPushResponse($response));
    }

    /** @test */
    public function it_will_return_a_css_link_header_for_css()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithCss'));

        $this->assertTrue($this->isServerPushResponse($response));
        $this->assertStringEndsWith('as=style', $response->headers->get('link'));
    }

    /** @test */
    public function it_will_return_a_js_link_header_for_js()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithJs'));

        $this->assertTrue($this->isServerPushResponse($response));
        $this->assertStringEndsWith('as=script', $response->headers->get('link'));
    }

    /** @test */
    public function it_will_return_an_image_link_header_for_images()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithImages'));

        $this->assertTrue($this->isServerPushResponse($response));
        $this->assertStringEndsWith('as=image', $response->headers->get('link'));
        $this->assertCount(6, explode(',', $response->headers->get('link')));
    }

    /** @test */
    public function it_returns_well_formatted_link_headers()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithCss'));

        $this->assertEquals('<css/test.css>; rel=preload; as=style', $response->headers->get('link'));
    }

    /** @test */
    public function it_will_return_correct_push_headers_for_multiple_assets()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithCssAndJs'));

        $this->assertTrue($this->isServerPushResponse($response));
        $this->assertTrue(str_contains($response->headers, 'style'));
        $this->assertTrue(str_contains($response->headers, 'script'));
        $this->assertCount(2, explode(',', $response->headers->get('link')));
    }

    /** @test */
    public function it_will_not_return_a_push_header_for_inline_js()
    {
        $request = new Request();

        $response = $this->middleware->handle($request, $this->getNext('pageWithJsInline'));

        $this->assertFalse($this->isServerPushResponse($response));
    }

    /** @test */
    public function it_will_return_limit_count_of_links()
    {
        $request = new Request();
        $limit = 2;

        $response = $this->middleware->handle($request, $this->getNext('pageWithImages'), $limit);

        $this->assertCount($limit, explode(',', $response->headers->get('link')));
    }

    /** @test */
    public function it_will_append_to_header_if_already_present()
    {
        $request = new Request();

        $next = $this->getNext('pageWithCss');

        $response = $this->middleware->handle($request, function ($request) use ($next) {
            $response = $next($request);
            $response->headers->set('Link', '<https://example.com/en>; rel="alternate"; hreflang="en"');

            return $response;
        });

        $this->assertTrue($this->isServerPushResponse($response));
        $this->assertStringStartsWith('<https://example.com/en>; rel="alternate"; hreflang="en",', $response->headers->get('link'));
        $this->assertStringEndsWith('as=style', $response->headers->get('link'));
    }

    /**
     * @param string $pageName
     *
     * @return \Closure
     */
    protected function getNext($pageName)
    {
        $html = $this->getHtml($pageName);

        $response = (new Response($html));

        return function ($request) use ($response) {
            return $response;
        };
    }

    /**
     * @param string $pageName
     *
     * @return string
     */
    protected function getHtml($pageName)
    {
        return file_get_contents(__DIR__."/Fixtures/{$pageName}.html");
    }

    private function isServerPushResponse($response)
    {
        return $response->headers->has('Link');
    }
}
