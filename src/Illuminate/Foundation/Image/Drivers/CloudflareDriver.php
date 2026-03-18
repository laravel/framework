<?php

namespace Illuminate\Foundation\Image\Drivers;

use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\PendingImageOptions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Foundation\Image\ImageException;

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
     */
    public function process(string $contents, PendingImageOptions $options): string
    {
        $response = $this->http
            ->withToken($this->apiToken)
            ->attach('file', $contents, 'image')
            ->post("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1");

        if ($response->failed()) {
            throw new ImageException(
                'Cloudflare image upload failed: '.$response->json('errors.0.message', 'Unknown error'),
            );
        }

        $imageId = $response->json('result.id');

        return $this->transformAndDelete($imageId, $response->json('result.variants', []), $options);
    }

    /**
     * Fetch the transformed image and delete the original from Cloudflare.
     */
    protected function transformAndDelete(string $imageId, array $variants, PendingImageOptions $options): string
    {
        try {
            if (empty($variants)) {
                throw new ImageException('Cloudflare did not return any image variants.');
            }

            $response = $this->http->get(
                $this->buildTransformUrl($variants[0], $options),
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
    protected function buildTransformUrl(string $baseUrl, PendingImageOptions $options): string
    {
        $params = [];

        if ($options->coverWidth !== null && $options->coverHeight !== null) {
            $params[] = "width={$options->coverWidth}";
            $params[] = "height={$options->coverHeight}";
            $params[] = 'fit=cover';
        }

        if ($options->format !== null) {
            $params[] = "format={$options->format}";
        }

        if ($options->quality !== null) {
            $params[] = "quality={$options->quality}";
        }

        if (empty($params)) {
            return $baseUrl;
        }

        // Cloudflare transform URL format: /cdn-cgi/image/{options}/{delivery_url}
        $transformParams = implode(',', $params);

        $parsed = parse_url($baseUrl);
        $host = $parsed['scheme'].'://'.$parsed['host'];

        return "{$host}/cdn-cgi/image/{$transformParams}{$parsed['path']}";
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
