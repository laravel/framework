<?php

namespace Illuminate\Foundation\Image\Drivers;

use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\Client\Factory as HttpFactory;

class CloudflareDriver implements Driver
{
    /**
     * Create a new Cloudflare driver instance.
     */
    public function __construct(
        protected HttpFactory $http,
        protected string $accountId,
        protected string $apiToken,
    ) {
        //
    }

    /**
     * Ensure the Cloudflare credentials are configured.
     *
     * @throws ImageException
     */
    public function ensureRequirementsAreMet(): void
    {
        if (empty($this->accountId) || empty($this->apiToken)) {
            throw new ImageException(
                'The Cloudflare image driver requires an account ID and API token.',
            );
        }
    }

    /**
     * Process the given image contents with the specified options.
     *
     * @throws ImageException
     */
    public function process(string $contents, PendingImageOptions $options): string
    {
        $sourceMimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        $targetFormat = $options->format ?? match ($sourceMimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => null,
        };

        if (! in_array($targetFormat, ['jpg', 'jpeg', 'webp'])) {
            throw new ImageException(
                'The Cloudflare image driver only supports JPEG or WebP as target format, please use [toJpg()] or [toWebp()].',
            );
        }

        $response = $this->http
            ->withToken($this->apiToken)
            ->attach('file', $contents, 'image')
            ->post("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1");

        if ($response->failed()) {
            throw new ImageException(
                'Cloudflare image upload failed: '.$response->json('errors.0.message', 'Unknown error'),
            );
        }

        return $this->transformAndDelete(
            $response->json('result.id'),
            $response->json('result.variants', []),
            $options,
            $contents,
            $sourceMimeType,
        );
    }

    /**
     * Fetch the transformed image and delete the original from Cloudflare.
     */
    protected function transformAndDelete(string $imageId, array $variants, PendingImageOptions $options, string $contents, string $sourceMimeType): string
    {
        try {
            if (empty($variants)) {
                throw new ImageException('Cloudflare did not return any image variants.');
            }

            $acceptMimeType = $options->format !== null
                ? match ($options->format) {
                    'webp' => 'image/webp',
                    'jpg', 'jpeg' => 'image/jpeg',
                }
            : $sourceMimeType;

            $request = $this->http->withHeaders([
                'Accept' => $acceptMimeType,
            ]);

            $response = $request->get(
                $this->buildTransformUrl($variants[0], $options, $contents),
            );

            if ($response->failed()) {
                throw new ImageException('Failed to fetch transformed image from Cloudflare.');
            }

            return $response->body();
        } finally {
            rescue(fn () => $this->deleteImage($imageId));
        }
    }

    /**
     * Build the Cloudflare transform URL with the given options.
     */
    protected function buildTransformUrl(string $baseUrl, PendingImageOptions $options, string $contents): string
    {
        $params = [];

        if ($options->coverWidth !== null && $options->coverHeight !== null) {
            $params[] = "width={$options->coverWidth}";
            $params[] = "height={$options->coverHeight}";
            $params[] = 'fit=cover';
        } elseif ($options->scaleWidth !== null && $options->scaleHeight !== null) {
            $params[] = "width={$options->scaleWidth}";
            $params[] = "height={$options->scaleHeight}";
            $params[] = 'fit=scale-down';
        } else {
            [$width, $height] = getimagesizefromstring($contents);

            $params[] = "width={$width}";
            $params[] = "height={$height}";
            $params[] = 'fit=scale-down';
        }

        if ($options->blur !== null) {
            $params[] = "blur={$options->blur}";
        }

        if ($options->greyscale) {
            $params[] = 'saturation=0';
        }

        if ($options->format !== null) {
            $params[] = 'format='.match ($options->format) {
                'jpg' => 'jpeg',
                default => $options->format,
            };
        }

        $params[] = 'quality='.($options->quality ?? PendingImageOptions::DEFAULT_QUALITY);

        // Cloudflare Images flexible variant format:
        // https://imagedelivery.net/{account_hash}/{image_id}/{params}
        // The params replace the variant name (e.g. /public) in the delivery URL.
        return preg_replace('#/[^/]+$#', '/'.implode(',', $params), $baseUrl);
    }

    /**
     * Delete the temporary image from Cloudflare.
     */
    protected function deleteImage(string $imageId): void
    {
        $this->http
            ->withToken($this->apiToken)
            ->delete("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/{$imageId}");
    }
}
