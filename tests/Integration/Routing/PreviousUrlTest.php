<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Session\SessionServiceProvider;

class PreviousUrlTest extends TestCase
{
    public function test_previous_url_without_session()
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
