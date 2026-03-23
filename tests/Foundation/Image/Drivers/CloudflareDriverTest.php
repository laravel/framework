<?php

namespace Illuminate\Tests\Foundation\Image\Drivers;

use Illuminate\Foundation\Image\Drivers\CloudflareDriver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\TestCase;

class CloudflareDriverTest extends TestCase
{
    public function test_ensure_requirements_throws_without_account_id()
    {
        $driver = new CloudflareDriver(new HttpFactory, '', 'token');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The Cloudflare image driver requires an account ID and API token.');

        $driver->ensureRequirementsAreMet();
    }

    public function test_ensure_requirements_throws_without_api_token()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', '');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The Cloudflare image driver requires an account ID and API token.');

        $driver->ensureRequirementsAreMet();
    }

    public function test_ensure_requirements_passes_with_credentials()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', 'token');

        $driver->ensureRequirementsAreMet();

        $this->assertTrue(true);
    }

    public function test_process_uploads_and_fetches_transformed_image()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('transformed-bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account-id', 'api-token');

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 100;

        $result = $driver->process('original-bytes', $options);

        $this->assertSame('transformed-bytes', $result);

        $http->assertSentCount(3); // upload + fetch + delete
    }

    public function test_process_sends_auth_token()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'my-account', 'my-secret-token');

        $driver->process('contents', new PendingImageOptions);

        $http->assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer my-secret-token')
                && str_contains($request->url(), 'my-account');
        });
    }

    public function test_process_throws_on_upload_failure()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => false,
                'errors' => [['message' => 'Invalid token']],
            ], 403),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'bad-token');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Cloudflare image upload failed: Invalid token');

        $driver->process('contents', new PendingImageOptions);
    }

    public function test_process_throws_on_empty_variants()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/client/v4/accounts/account/images/v1' => $http->sequence()
                ->push(['success' => true, 'result' => ['id' => 'img-123', 'variants' => []]])
                ->push(['success' => true]), // delete
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Cloudflare did not return any image variants.');

        $driver->process('contents', new PendingImageOptions);
    }

    public function test_process_throws_on_fetch_failure()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/client/v4/accounts/account/images/v1' => $http->sequence()
                ->push(['success' => true, 'result' => ['id' => 'img-123', 'variants' => ['https://imagedelivery.net/abc/img-123/public']]])
                ->push(['success' => true]), // delete
            'imagedelivery.net/*' => $http->response('', 500),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Failed to fetch transformed image from Cloudflare.');

        $driver->process('contents', new PendingImageOptions);
    }

    public function test_process_deletes_image_even_on_failure()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/client/v4/accounts/account/images/v1' => $http->sequence()
                ->push(['success' => true, 'result' => ['id' => 'img-123', 'variants' => ['https://imagedelivery.net/abc/img-123/public']]])
                ->push(['success' => true]), // delete
            'imagedelivery.net/*' => $http->response('', 500),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        try {
            $driver->process('contents', new PendingImageOptions);
        } catch (ImageException) {
            // expected
        }

        $http->assertSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'img-123');
        });
    }

    public function test_build_transform_url_with_cover_options()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $options = new PendingImageOptions;
        $options->coverWidth = 200;
        $options->coverHeight = 150;

        $driver->process('contents', $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'w=200')
                && str_contains($request->url(), 'h=150')
                && str_contains($request->url(), 'fit=cover')
                && str_contains($request->url(), 'imagedelivery.net/abc/img-123/');
        });
    }

    public function test_build_transform_url_with_scale_options()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $options = new PendingImageOptions;
        $options->scaleWidth = 800;
        $options->scaleHeight = 600;

        $driver->process('contents', $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'w=800')
                && str_contains($request->url(), 'h=600')
                && str_contains($request->url(), 'fit=scale-down');
        });
    }

    public function test_build_transform_url_with_blur()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $options = new PendingImageOptions;
        $options->blur = 15;

        $driver->process('contents', $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'blur=15');
        });
    }

    public function test_build_transform_url_with_greyscale()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $options = new PendingImageOptions;
        $options->greyscale = true;

        $driver->process('contents', $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'saturation=0');
        });
    }

    public function test_format_uses_accept_header()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $options = new PendingImageOptions;
        $options->format = 'webp';

        $driver->process('contents', $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'imagedelivery.net')
                && $request->hasHeader('Accept', 'image/webp');
        });
    }

    public function test_quality_in_transform_url()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'id' => 'img-123',
                    'variants' => ['https://imagedelivery.net/abc/img-123/public'],
                ],
            ]),
            'imagedelivery.net/*' => $http->response('bytes'),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token');

        $options = new PendingImageOptions;
        $options->quality = 90;

        $driver->process('contents', $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'q=90');
        });
    }
}
