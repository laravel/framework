<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class MarkdownResponseTest extends TestCase
{
    public function testMarkdownResponse()
    {
        Route::get('/markdown', function () {
            return response()->markdown("# Hello\n\nWorld");
        });

        $response = $this->get('/markdown');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertContent("# Hello\n\nWorld");
    }

    public function testMarkdownResponseWithStatusAndHeaders()
    {
        Route::get('/markdown', function () {
            return response()->markdown('# Not Found', 404, [
                'Content-Type' => 'text/html',
                'X-Foo' => 'bar',
            ]);
        });

        $response = $this->get('/markdown');

        $response->assertNotFound();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertHeader('X-Foo', 'bar');
        $response->assertContent('# Not Found');
    }

    public function testMarkdownResponseCanBeNegotiated()
    {
        Route::get('/markdown', function (Request $request) {
            return $request->wantsMarkdown()
                ? response()->markdown('# Hello')
                : response('<h1>Hello</h1>');
        });

        $this->get('/markdown', ['Accept' => 'text/markdown'])
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertContent('# Hello');

        $this->get('/markdown', ['Accept' => 'text/html'])
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertContent('<h1>Hello</h1>');
    }
}
