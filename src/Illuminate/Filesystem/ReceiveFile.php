<?php

namespace Illuminate\Filesystem;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\PathTraversalDetected;

class ReceiveFile
{
    /**
     * Create a new invokable controller to receive files.
     */
    public function __construct(
        protected string $disk,
        protected array $config,
        protected bool $isProduction,
    ) {
        //
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $path): Response
    {
        abort_unless(
            $this->hasValidSignature($request),
            $this->isProduction ? 404 : 403
        );

        try {
            Storage::disk($this->disk)->put($path, $request->getContent(true));

            return response()->noContent();
        } catch (PathTraversalDetected) {
            abort(404);
        }
    }

    /**
     * Determine if the request has a valid signature if applicable.
     *
     * The request body is the file contents, so it is not compared against the signed parameters.
     */
    protected function hasValidSignature(Request $request): bool
    {
        return filter_var($request->query('upload', false), FILTER_VALIDATE_BOOLEAN)
            && URL::hasCorrectSignature($request, absolute: false)
            && URL::signatureHasNotExpired($request);
    }
}
