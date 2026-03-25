<?php

namespace Illuminate\Tests\Foundation\Image\Drivers;

use Illuminate\Foundation\Image\Drivers\CloudflareDriver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class CloudflareDriverTest extends TestCase
{
    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }

    public function test_ensure_requirements_throws_without_account_id()
    {
        $driver = new CloudflareDriver(new HttpFactory, '', 'token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The Cloudflare image driver requires an account ID and API token.');

        $driver->ensureRequirementsAreMet();
    }

    public function test_ensure_requirements_throws_without_api_token()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', '', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The Cloudflare image driver requires an account ID and API token.');

        $driver->ensureRequirementsAreMet();
    }

    public function test_ensure_requirements_passes_with_credentials()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', 'token', 'laravel-image');

        $driver->ensureRequirementsAreMet();

        $this->assertTrue(true);
    }

    public function test_ensure_requirements_throws_without_prefix()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', 'token', '');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The Cloudflare image driver requires a prefix for temporary uploads.');

        $driver->ensureRequirementsAreMet();
    }

    public function test_throws_for_unsupported_input_format()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', 'token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The image format [text/plain] is not supported by the Cloudflare driver.');

        $driver->process('not-an-image', new PendingImageOptions);
    }

    public function test_throws_for_bmp_input_format()
    {
        $driver = new CloudflareDriver(new HttpFactory, 'account', 'token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('The image format [image/bmp] is not supported by the Cloudflare driver.');

        $im = imagecreatetruecolor(1, 1);
        ob_start();
        imagebmp($im);
        $bmp = ob_get_clean();

        $driver->process($bmp, new PendingImageOptions);
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

        $driver = new CloudflareDriver($http, 'account-id', 'api-token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 100;

        $result = $driver->process($this->fakeImageContents(), $options);

        $this->assertSame('transformed-bytes', $result);

        $http->assertSentCount(3); // upload + fetch + delete
    }

    public function test_process_uploads_with_random_filename()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 100;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'api.cloudflare.com')) {
                return false;
            }

            $body = $request->body();

            return str_contains($body, 'filename="') && ! str_contains($body, 'filename="image"');
        });
    }

    public function test_process_uploads_with_laravel_image_prefix()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 100;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'api.cloudflare.com')) {
                return false;
            }

            return str_contains($request->body(), 'laravel-image/');
        });
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

        $driver = new CloudflareDriver($http, 'my-account', 'my-secret-token', 'laravel-image');

        $driver->process($this->fakeImageContents(), new PendingImageOptions);

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

        $driver = new CloudflareDriver($http, 'account', 'bad-token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Cloudflare image upload failed: Invalid token');

        $driver->process($this->fakeImageContents(), new PendingImageOptions);
    }

    public function test_process_upload_failure_preserves_original_exception()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response(['success' => false], 403),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        try {
            $driver->process($this->fakeImageContents(), new PendingImageOptions);
        } catch (ImageException $e) {
            $this->assertNotNull($e->getPrevious());
            $this->assertInstanceOf(\Illuminate\Http\Client\RequestException::class, $e->getPrevious());

            return;
        }

        $this->fail('ImageException was not thrown.');
    }

    public function test_process_fetch_failure_preserves_original_exception()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/client/v4/accounts/account/images/v1' => $http->sequence()
                ->push(['success' => true, 'result' => ['id' => 'img-123', 'variants' => ['https://imagedelivery.net/abc/img-123/public']]])
                ->push(['success' => true]),
            'imagedelivery.net/*' => $http->response('', 500),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        try {
            $driver->process($this->fakeImageContents(), new PendingImageOptions);
        } catch (ImageException $e) {
            $this->assertNotNull($e->getPrevious());
            $this->assertInstanceOf(\Illuminate\Http\Client\RequestException::class, $e->getPrevious());

            return;
        }

        $this->fail('ImageException was not thrown.');
    }

    public function test_prune_orphaned_list_failure_preserves_original_exception()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response(['success' => false], 500),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        try {
            $driver->pruneOrphaned();
        } catch (ImageException $e) {
            $this->assertNotNull($e->getPrevious());
            $this->assertInstanceOf(\Illuminate\Http\Client\RequestException::class, $e->getPrevious());

            return;
        }

        $this->fail('ImageException was not thrown.');
    }

    public function test_process_throws_on_empty_variants()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/client/v4/accounts/account/images/v1' => $http->sequence()
                ->push(['success' => true, 'result' => ['id' => 'img-123', 'variants' => []]])
                ->push(['success' => true]), // delete
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Cloudflare did not return any image variants.');

        $driver->process($this->fakeImageContents(), new PendingImageOptions);
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Failed to fetch transformed image from Cloudflare: Unknown error');

        $driver->process($this->fakeImageContents(), new PendingImageOptions);
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        try {
            $driver->process($this->fakeImageContents(), new PendingImageOptions);
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 200;
        $options->coverHeight = 150;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'width=200')
                && str_contains($request->url(), 'height=150')
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->scaleWidth = 800;
        $options->scaleHeight = 600;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'width=800')
                && str_contains($request->url(), 'height=600')
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->blur = 15;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'blur=15');
        });
    }

    public function test_build_transform_url_with_sharpen()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->sharpen = 50;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'sharpen=5');
        });
    }

    public function test_build_transform_url_with_flip()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->flip = true;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'flip=v');
        });
    }

    public function test_build_transform_url_with_flop()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->flop = true;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'flip=h');
        });
    }

    public function test_build_transform_url_with_flip_and_flop()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->flip = true;
        $options->flop = true;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'flip=hv');
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->greyscale = true;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'saturation=0');
        });
    }

    public function test_format_in_transform_url()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->format = 'webp';

        $driver->process($this->fakeImageContents(200, 150), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'format=webp')
                && str_contains($request->url(), 'width=200')
                && str_contains($request->url(), 'height=150')
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->quality = 90;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'quality=90');
        });
    }

    public function test_default_quality_is_sent_when_not_specified()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 100;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'quality=75');
        });
    }

    public function test_jpg_format_is_mapped_to_jpeg()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->format = 'jpg';

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'format=jpeg')
                && ! str_contains($request->url(), 'format=jpg');
        });
    }

    public function test_accept_header_for_jpg_format()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->format = 'jpg';

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return $request->hasHeader('Accept', 'image/jpeg');
        });
    }

    public function test_accept_header_uses_source_mime_when_no_format()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 100;
        $options->coverHeight = 100;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return $request->hasHeader('Accept', 'image/jpeg');
        });
    }

    public function test_sharpen_scale_maps_100_to_10()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->sharpen = 100;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'sharpen=10');
        });
    }

    public function test_sharpen_scale_maps_1_to_0()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->sharpen = 1;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'sharpen=0');
        });
    }

    public function test_original_dimensions_used_when_no_cover_or_scale()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->blur = 10;

        $driver->process($this->fakeImageContents(300, 200), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'width=300')
                && str_contains($request->url(), 'height=200')
                && str_contains($request->url(), 'fit=scale-down');
        });
    }

    public function test_all_options_combined_in_url()
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

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $options = new PendingImageOptions;
        $options->coverWidth = 200;
        $options->coverHeight = 200;
        $options->blur = 5;
        $options->greyscale = true;
        $options->sharpen = 50;
        $options->flip = true;
        $options->format = 'webp';
        $options->quality = 80;

        $driver->process($this->fakeImageContents(), $options);

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'width=200')
                && str_contains($request->url(), 'height=200')
                && str_contains($request->url(), 'fit=cover')
                && str_contains($request->url(), 'blur=5')
                && str_contains($request->url(), 'saturation=0')
                && str_contains($request->url(), 'sharpen=5')
                && str_contains($request->url(), 'flip=v')
                && str_contains($request->url(), 'format=webp')
                && str_contains($request->url(), 'quality=80');
        });
    }

    public function test_prune_orphaned_deletes_old_prefixed_images()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->sequence()
                ->push([
                    'success' => true,
                    'result' => [
                        'images' => [
                            ['id' => 'laravel-image/abc123', 'uploaded' => '2020-01-01T00:00:00Z'],
                            ['id' => 'laravel-image/def456', 'uploaded' => '2020-01-01T00:00:00Z'],
                            ['id' => 'other-app/ghi789', 'uploaded' => '2020-01-01T00:00:00Z'],
                        ],
                    ],
                ])
                ->push(['success' => true])  // delete abc123
                ->push(['success' => true]), // delete def456
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSentCount(3); // list + 2 deletes (not 3 — other-app is skipped)

        $http->assertSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'laravel-image/abc123');
        });

        $http->assertSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'laravel-image/def456');
        });

        $http->assertNotSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'other-app/ghi789');
        });
    }

    public function test_prune_orphaned_skips_recently_uploaded_images()
    {
        $http = new HttpFactory;

        $recentTimestamp = (new \DateTimeImmutable)->format('c');

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'images' => [
                        ['id' => 'laravel-image/recent', 'uploaded' => $recentTimestamp],
                    ],
                ],
            ]),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSentCount(1); // only the list request, no delete
    }

    public function test_prune_orphaned_skips_images_without_prefix()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'images' => [
                        ['id' => 'user-uploaded/photo.jpg', 'uploaded' => '2020-01-01T00:00:00Z'],
                        ['id' => 'some-other-image', 'uploaded' => '2020-01-01T00:00:00Z'],
                    ],
                ],
            ]),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSentCount(1); // only the list request, no deletes
    }

    public function test_prune_orphaned_throws_on_list_failure()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response(['success' => false], 403),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $this->expectException(ImageException::class);
        $this->expectExceptionMessage('Failed to list images from Cloudflare: Unknown error');

        $driver->pruneOrphaned();
    }

    public function test_prune_orphaned_with_empty_image_list()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => [
                    'images' => [],
                ],
            ]),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSentCount(1);
    }

    public function test_prune_orphaned_paginates_through_multiple_pages()
    {
        $http = new HttpFactory;

        $page1Images = array_map(fn ($i) => [
            'id' => "laravel-image/img-{$i}",
            'uploaded' => '2020-01-01T00:00:00Z',
        ], range(1, 100));

        $page2Images = [
            ['id' => 'laravel-image/img-101', 'uploaded' => '2020-01-01T00:00:00Z'],
        ];

        $sequence = $http->sequence()
            ->push(['success' => true, 'result' => ['images' => $page1Images]]);

        for ($i = 0; $i < 100; $i++) {
            $sequence->push(['success' => true]);
        }

        $sequence
            ->push(['success' => true, 'result' => ['images' => $page2Images]])
            ->push(['success' => true]); // delete img-101

        $http->fake(['api.cloudflare.com/*' => $sequence]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        // page 1 list + 100 deletes + page 2 list + 1 delete
        $http->assertSentCount(103);
    }

    public function test_prune_orphaned_uses_correct_api_endpoint()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => ['images' => []],
            ]),
        ]);

        $driver = new CloudflareDriver($http, 'my-account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSent(function (Request $request) {
            return str_contains($request->url(), 'my-account/images/v1')
                && $request->method() === 'GET';
        });
    }

    public function test_prune_orphaned_sends_auth_token()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->response([
                'success' => true,
                'result' => ['images' => []],
            ]),
        ]);

        $driver = new CloudflareDriver($http, 'account', 'my-token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer my-token');
        });
    }

    public function test_prune_orphaned_respects_custom_prefix()
    {
        $http = new HttpFactory;

        $http->fake([
            'api.cloudflare.com/*' => $http->sequence()
                ->push([
                    'success' => true,
                    'result' => [
                        'images' => [
                            ['id' => 'my-app/abc', 'uploaded' => '2020-01-01T00:00:00Z'],
                            ['id' => 'laravel-image/def', 'uploaded' => '2020-01-01T00:00:00Z'],
                        ],
                    ],
                ])
                ->push(['success' => true]), // delete my-app/abc
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'my-app');

        $driver->pruneOrphaned();

        $http->assertSentCount(2); // list + 1 delete (only my-app prefix)

        $http->assertSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'my-app/abc');
        });

        $http->assertNotSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'laravel-image/def');
        });
    }

    public function test_prune_orphaned_with_mix_of_old_and_recent_prefixed_images()
    {
        $http = new HttpFactory;

        $recentTimestamp = (new \DateTimeImmutable)->format('c');

        $http->fake([
            'api.cloudflare.com/*' => $http->sequence()
                ->push([
                    'success' => true,
                    'result' => [
                        'images' => [
                            ['id' => 'laravel-image/old', 'uploaded' => '2020-01-01T00:00:00Z'],
                            ['id' => 'laravel-image/recent', 'uploaded' => $recentTimestamp],
                            ['id' => 'laravel-image/also-old', 'uploaded' => '2020-06-15T00:00:00Z'],
                        ],
                    ],
                ])
                ->push(['success' => true])  // delete old
                ->push(['success' => true]), // delete also-old
        ]);

        $driver = new CloudflareDriver($http, 'account', 'token', 'laravel-image');

        $driver->pruneOrphaned();

        $http->assertSentCount(3); // list + 2 deletes (recent skipped)

        $http->assertNotSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'laravel-image/recent');
        });
    }
}
