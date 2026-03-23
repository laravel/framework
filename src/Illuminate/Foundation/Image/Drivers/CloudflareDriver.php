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
            $params[] = "w={$options->coverWidth}";
            $params[] = "h={$options->coverHeight}";
            $params[] = 'fit=cover';
        }

        if ($options->scaleWidth !== null && $options->scaleHeight !== null) {
            $params[] = "w={$options->scaleWidth}";
            $params[] = "h={$options->scaleHeight}";
            $params[] = 'fit=scale-down';
        }

        if ($options->blur !== null) {
            $params[] = "blur={$options->blur}";
        }

        if ($options->greyscale) {
            $params[] = 'saturation=0';
        }

        if ($options->format !== null) {
            $params[] = "f={$options->format}";
        }

        if ($options->quality !== null) {
            $params[] = "q={$options->quality}";
        }

        if (empty($params) && $options->orient) {
            $params[] = 'metadata=none';
        }

        // Cloudflare Images flexible variant format:
        // https://imagedelivery.net/{account_hash}/{image_id}/{params}
        // The params replace the variant name (e.g. /public) in the delivery URL.
        $variantParams = implode(',', $params);

        return preg_replace('#/[^/]+$#', '/'.$variantParams, $baseUrl);
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
