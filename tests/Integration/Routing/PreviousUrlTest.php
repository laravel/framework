<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class PreviousUrlTest extends TestCase
{
    public function testPreviousUrlWithoutSession()
    {
        Route::post('/previous-url', function (DummyFormRequest $request) {
            return 'OK';
        });

        $response = $this->postJson('/previous-url');

        $this->assertEquals(422, $response->status());
    }

    protected function getApplicationProviders($app)
    {
        $providers = parent::getApplicationProviders($app);

        return array_filter($providers, function ($provider) {
            return $provider !== SessionServiceProvider::class;
        });
    }
}

class DummyFormRequest extends FormRequest
{
    public function rules()
    {
        return [
            'foo' => [
                'required',
                'string',
            ],
        ];
    }
}
