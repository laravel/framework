<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class RunSolutionController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->isLocalRequest($request)) {
            return response()->json(['success' => false, 'output' => 'Forbidden.'], 403);
        }

        $command = $request->input('command');

        if (! is_string($command) || blank($command)) {
            return response()->json(['success' => false, 'output' => 'Invalid command.'], 422);
        }

        if (! $this->isAllowedCommand($command)) {
            return response()->json(['success' => false, 'output' => 'Command not allowed.'], 403);
        }

        try {
            $outputBuffer = new BufferedOutput();

            $exitCode = Artisan::call($command, [], $outputBuffer);

            $output = $outputBuffer->fetch();

            return response()->json([
                'success' => $exitCode === 0,
                'output' => trim($output),
                'exitCode' => $exitCode,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    private function isLocalRequest(Request $request): bool
    {
        $ip = $request->ip();

        return in_array($ip, ['127.0.0.1', '::1', '10.0.2.2'], true)
            || str_starts_with((string) $ip, '192.168.')
            || str_starts_with((string) $ip, '10.');
    }

    private function isAllowedCommand(string $command): bool
    {
        $allowed = [
            'migrate',
            'key:generate',
            'config:clear',
            'cache:clear',
            'view:clear',
            'route:clear',
            'optimize:clear',
        ];

        return in_array($command, $allowed, true);
    }
}
