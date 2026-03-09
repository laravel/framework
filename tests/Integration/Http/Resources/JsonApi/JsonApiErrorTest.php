<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiError;
use Illuminate\Http\Resources\JsonApi\JsonApiErrorResponse;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JsonApiErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        JsonApiResource::flushState();
    }

    protected function defineRoutes($router)
    {
        parent::defineRoutes($router);

        $router->get('errors/validation', function () {
            $validator = \Illuminate\Support\Facades\Validator::make(
                ['title' => ''],
                ['title' => 'required', 'body' => 'required']
            );

            throw new ValidationException($validator);
        });

        $router->get('errors/custom', function () {
            return JsonApiErrorResponse::make([
                JsonApiError::make('Name is required', '422')
                    ->title('Validation Error')
                    ->pointer('/data/attributes/name'),
            ], 422);
        });

        $router->get('errors/not-found', function () {
            throw new NotFoundHttpException('Post not found');
        });

        $router->get('errors/server-error', function () {
            throw new \RuntimeException('Something went wrong');
        });

        $router->get('errors/multiple', function () {
            return JsonApiErrorResponse::make([
                JsonApiError::make('Title is required', '422')
                    ->pointer('/data/attributes/title'),
                JsonApiError::make('Body must be at least 10 characters', '422')
                    ->pointer('/data/attributes/body'),
            ], 422);
        });
    }

    public function testJsonApiErrorCanBeConstructedWithFluentApi()
    {
        $error = JsonApiError::make()
            ->id('error-1')
            ->status('422')
            ->code('VALIDATION_ERROR')
            ->title('Invalid Attribute')
            ->detail('Title must be at least 3 characters.')
            ->pointer('/data/attributes/title')
            ->about('https://example.com/docs/errors/validation')
            ->type('https://example.com/errors/validation')
            ->meta(['timestamp' => '2024-01-01']);

        $this->assertSame([
            'id' => 'error-1',
            'status' => '422',
            'code' => 'VALIDATION_ERROR',
            'title' => 'Invalid Attribute',
            'detail' => 'Title must be at least 3 characters.',
            'source' => ['pointer' => '/data/attributes/title'],
            'links' => [
                'about' => 'https://example.com/docs/errors/validation',
                'type' => 'https://example.com/errors/validation',
            ],
            'meta' => ['timestamp' => '2024-01-01'],
        ], $error->toArray());
    }

    public function testJsonApiErrorOmitsNullValues()
    {
        $error = JsonApiError::make('Something went wrong', '500');

        $this->assertSame([
            'status' => '500',
            'detail' => 'Something went wrong',
        ], $error->toArray());
    }

    public function testJsonApiErrorSupportsParameterSource()
    {
        $error = JsonApiError::make('Invalid sort field', '400')
            ->parameter('sort');

        $this->assertSame([
            'status' => '400',
            'detail' => 'Invalid sort field',
            'source' => ['parameter' => 'sort'],
        ], $error->toArray());
    }

    public function testJsonApiErrorSupportsHeaderSource()
    {
        $error = JsonApiError::make('Unsupported media type', '415')
            ->header('Content-Type');

        $this->assertSame([
            'status' => '415',
            'detail' => 'Unsupported media type',
            'source' => ['header' => 'Content-Type'],
        ], $error->toArray());
    }

    public function testJsonApiErrorSupportsMultipleSourceFields()
    {
        $error = JsonApiError::make('Error')
            ->pointer('/data/attributes/title')
            ->parameter('include');

        $this->assertSame([
            'pointer' => '/data/attributes/title',
            'parameter' => 'include',
        ], $error->toArray()['source']);
    }

    public function testJsonApiErrorIsJsonSerializable()
    {
        $error = JsonApiError::make('Not found', '404');

        $this->assertSame(
            '{"status":"404","detail":"Not found"}',
            json_encode($error)
        );
    }

    public function testCustomErrorResponse()
    {
        $this->getJson('errors/custom')
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '422',
                        'title' => 'Validation Error',
                        'detail' => 'Name is required',
                        'source' => ['pointer' => '/data/attributes/name'],
                    ],
                ],
            ]);
    }

    public function testMultipleErrors()
    {
        $this->getJson('errors/multiple')
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonCount(2, 'errors')
            ->assertJsonPath('errors.0.detail', 'Title is required')
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/title')
            ->assertJsonPath('errors.1.detail', 'Body must be at least 10 characters')
            ->assertJsonPath('errors.1.source.pointer', '/data/attributes/body');
    }

    public function testFromValidationException()
    {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['title' => ''],
            ['title' => 'required', 'body' => 'required']
        );

        $exception = ValidationException::withMessages($validator->errors()->toArray());
        $response = JsonApiErrorResponse::fromValidationException($exception);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/vnd.api+json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);

        $this->assertCount(2, $content['errors']);
        $this->assertSame('Validation Error', $content['errors'][0]['title']);
        $this->assertSame('422', $content['errors'][0]['status']);
        $this->assertSame('/data/attributes/title', $content['errors'][0]['source']['pointer']);
        $this->assertSame('/data/attributes/body', $content['errors'][1]['source']['pointer']);
    }

    public function testFromThrowableWithHttpException()
    {
        $exception = new NotFoundHttpException('Post not found');

        $response = JsonApiErrorResponse::fromThrowable($exception);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/vnd.api+json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);

        $this->assertCount(1, $content['errors']);
        $this->assertSame('404', $content['errors'][0]['status']);
        $this->assertSame('Post not found', $content['errors'][0]['title']);
    }

    public function testFromThrowableWithGenericException()
    {
        $exception = new \RuntimeException('Something broke');

        $response = JsonApiErrorResponse::fromThrowable($exception);

        $this->assertSame(500, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertCount(1, $content['errors']);
        $this->assertSame('500', $content['errors'][0]['status']);
        $this->assertSame('Server Error', $content['errors'][0]['title']);
        $this->assertArrayNotHasKey('detail', $content['errors'][0]);
    }

    public function testFromThrowableInDebugMode()
    {
        $exception = new \RuntimeException('Something broke');

        $response = JsonApiErrorResponse::fromThrowable($exception, debug: true);

        $content = json_decode($response->getContent(), true);

        $this->assertSame('Something broke', $content['errors'][0]['detail']);
        $this->assertSame('RuntimeException', $content['errors'][0]['meta']['exception']);
        $this->assertArrayHasKey('file', $content['errors'][0]['meta']);
        $this->assertArrayHasKey('line', $content['errors'][0]['meta']);
    }

    public function testErrorResponseIncludesJsonApiInformation()
    {
        JsonApiResource::configure(version: '1.1');

        $response = JsonApiErrorResponse::make([
            JsonApiError::make('Error', '400'),
        ]);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('jsonapi', $content);
        $this->assertSame('1.1', $content['jsonapi']['version']);
    }

    public function testErrorResponseDoesNotIncludeDataKey()
    {
        $response = JsonApiErrorResponse::make([
            JsonApiError::make('Error', '400'),
        ]);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('data', $content);
        $this->assertArrayHasKey('errors', $content);
    }

    public function testValidationExceptionAutoFormatsForJsonApiRequests()
    {
        $this->json('GET', 'errors/validation', [], ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonStructure(['errors' => [['status', 'title', 'detail', 'source']]])
            ->assertJsonPath('errors.0.title', 'Validation Error')
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/title')
            ->assertJsonMissing(['message', 'data']);
    }

    public function testValidationExceptionUsesStandardFormatForRegularJsonRequests()
    {
        $this->getJson('errors/validation')
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonMissing(['source', 'pointer']);
    }

    public function testNotFoundExceptionAutoFormatsForJsonApiRequests()
    {
        $this->json('GET', 'errors/not-found', [], ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('errors.0.status', '404')
            ->assertJsonPath('errors.0.title', 'Post not found')
            ->assertJsonMissing(['data']);
    }

    public function testServerErrorAutoFormatsForJsonApiRequests()
    {
        $this->json('GET', 'errors/server-error', [], ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('errors.0.status', '500')
            ->assertJsonPath('errors.0.title', 'Server Error');
    }

    public function testServerErrorIncludesDebugInfoWhenEnabled()
    {
        config(['app.debug' => true]);

        $this->json('GET', 'errors/server-error', [], ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('errors.0.status', '500')
            ->assertJsonPath('errors.0.detail', 'Something went wrong')
            ->assertJsonPath('errors.0.meta.exception', 'RuntimeException');
    }
}
