<?php

namespace Illuminate\Foundation\Http\Controllers;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class KeyGenerationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Generate a new application key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function generate(Request $request): JsonResponse
    {
        if (! $this->app->hasDebugModeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Key generation is only available in debug mode.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $key = $this->generateRandomKey();

            if (! $this->setKeyInEnvironmentFile($key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to set application key. Ensure the .env file is writable.',
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->app['config']['app.key'] = $key;

            return response()->json([
                'success' => true,
                'message' => 'Application key set successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the application key.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            Encrypter::generateKey($this->app['config']['app.cipher'])
        );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $path = $this->app->environmentFilePath();

        if (! is_file($path) || ! is_writable($path)) {
            return false;
        }

        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY='.$key,
            $input = file_get_contents($path)
        );

        if ($replaced === $input || $replaced === null) {
            return false;
        }

        file_put_contents($path, $replaced);

        return true;
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->app['config']['app.key'], '/');

        return "/^APP_KEY{$escaped}/m";
    }
}
