<?php
declare(strict_types=1);

namespace Illuminate\Http\Client\Examples;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Middleware\AbstractHttpMiddleware;
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
        $date = Carbon::now()->format('Y-m-d H:i:s');
        $myFile = fopen('./logs/'.$date.".txt", "w");
        fwrite($myFile, json_encode($request->getBody()->getContents()));
        fwrite($myFile, json_encode($response->getBody()->getContents()));
    }
}
