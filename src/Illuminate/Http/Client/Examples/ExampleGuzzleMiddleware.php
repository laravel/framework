<?php
declare(strict_types=1);

namespace Illuminate\Http\Client\Examples;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Middleware\AbstractHttpMiddleware;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExampleGuzzleMiddleware extends AbstractHttpMiddleware
{

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception|null $reason
     * @return void
     */
    protected function getRequestOptions(RequestInterface $request, ResponseInterface $response, Exception $reason = null): void
    {
        Log::info('Request', [$request->getBody()->getContents()]);
        Log::info('Response', [$response->getBody()->getContents()]);
    }
}
