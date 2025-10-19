<?php

namespace Illuminate\Foundation\Http\Controllers;

use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KeyGenerationController extends Controller
{
    /**
     * Generate application key via HTTP request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateKey(Request $request): JsonResponse
    {
        // Only allow this in debug mode for security
        if (! config('app.debug')) {
            return response()->json([
                'success' => false,
                'message' => 'Key generation is only available in debug mode.',
            ], 403);
        }

        try {
            // Create a new instance of the KeyGenerateCommand
            $command = new KeyGenerateCommand();
            $command->setLaravel(app());

            // Generate the key
            $key = $command->generateRandomKey();

            // Set the key in the environment file
            if (! $command->setKeyInEnvironmentFile($key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to set application key in environment file.',
                ], 500);
            }

            // Update the config cache
            app()['config']['app.key'] = $key;

            return response()->json([
                'success' => true,
                'message' => 'Application key generated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate application key: '.$e->getMessage(),
            ], 500);
        }
    }
}
