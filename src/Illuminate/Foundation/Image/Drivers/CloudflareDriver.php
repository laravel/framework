<?php

namespace Illuminate\Foundation\Image\Drivers;

use DateTimeImmutable;
use finfo;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;

class CloudflareDriver implements Driver
{
    /**
     * Create a new Cloudflare driver instance.
     */
    public function __construct(
        protected HttpFactory $http,
        protected string $accountId,
        protected string $apiToken,
        protected string $prefix,
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

        if (empty($this->prefix)) {
            throw new ImageException(
                'The Cloudflare image driver requires a prefix for temporary uploads.',
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
        $sourceMimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! in_array($sourceMimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            throw new ImageException("The image format [{$sourceMimeType}] is not supported by the Cloudflare driver.");
        }

        $id = $this->prefix.'/'.Str::random(40).match ($sourceMimeType) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
        };

        $response = $this->http
            ->withToken($this->apiToken)
            ->attach('file', $contents, basename($id))
            ->post("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1", [
                'id' => $id,
            ]);

        if ($response->failed()) {
            throw new ImageException(
                'Cloudflare image upload failed: '.$response->json('errors.0.message', 'Unknown error'),
                previous: $response->toException(),
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
     *
     * @param  array<int, string>  $variants
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
                throw new ImageException(
                    'Failed to fetch transformed image from Cloudflare: '.$response->json('errors.0.message', 'Unknown error'),
                    previous: $response->toException(),
                );
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

        if ($options->sharpen !== null) {
            $params[] = 'sharpen='.max(0, min(10, round($options->sharpen / 10)));
        }

        if ($options->flip && $options->flop) {
            $params[] = 'flip=hv';
        } elseif ($options->flip) {
            $params[] = 'flip=v';
        } elseif ($options->flop) {
            $params[] = 'flip=h';
        }

        if ($options->format !== null) {
            $params[] = 'format='.match ($options->format) {
                'jpg' => 'jpeg',
                default => $options->format,
            };
        }

        $params[] = 'quality='.($options->quality ?? PendingImageOptions::DEFAULT_QUALITY);

        return preg_replace('#/[^/]+$#', '/'.implode(',', $params), $baseUrl);
    }

    /**
     * Delete orphaned images from Cloudflare that match the configured prefix.
     */
    public function pruneOrphaned(): void
    {
        $page = 1;

        do {
            $response = $this->http
                ->withToken($this->apiToken)
                ->get("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1", [
                    'per_page' => 100,
                    'page' => $page,
                ]);

            if ($response->failed()) {
                throw new ImageException(
                    'Failed to list images from Cloudflare: '.$response->json('errors.0.message', 'Unknown error'),
                    previous: $response->toException(),
                );
            }

            $images = $response->json('result.images', []);

            collect($images)
                ->filter(fn (array $image) => str_starts_with($image['id'], $this->prefix.'/'))
                ->reject(fn (array $image) => (new DateTimeImmutable($image['uploaded']))->getTimestamp() > time() - 300)
                ->pluck('id')
                ->chunk(10)
                ->each(fn ($chunk) => $this->http->pool(fn (Pool $pool) => $chunk->map(
                    fn (string $id) => $pool->withToken($this->apiToken)
                        ->delete("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/{$id}"),
                )->all()));

            $page++;
        } while (count($images) === 100);
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
