<?php
declare(strict_types=1);

namespace Illuminate\Http\Client\Middleware;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractHttpMiddleware
{
    protected Carbon $startDateTime;
    protected ?TransferStats $logStats = null;

    /**
     * Middleware that logs requests, responses, and errors using a message formatter.
     */
    public function __construct()
    {
        $this->startDateTime = Carbon::now();
    }

    /**
     * @param callable $handler
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            if (isset($options['on_stats'])) {
                $options['on_stats'] = function (TransferStats $stats) {
                    $this->logStats = $stats;
                };
            }

            return $handler($request, $options)
                ->then(
                    $this->handleSuccess($request, $options),
                    $this->handleFailure($request, $options)
                );
        };
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return callable
     */
    private function handleSuccess(
        RequestInterface $request,
        array $options = []
    ): callable
    {
        return function (ResponseInterface $response) use ($request, $options) {
            $this->getRequestOptions($request, $response);

            return $response;
        };
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return callable
     */
    private function handleFailure(
        RequestInterface $request,
        array $options = []
    ): callable
    {
        return function (Exception $reason) use ($request, $options) {
            $response = $reason instanceof RequestException ? $reason->getResponse() : null;
            $this->getRequestOptions($request, $response, $reason);

            return Create::rejectionFor($reason);
        };
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception|null $reason
     * @return void
     */
    protected abstract function getRequestOptions(
        RequestInterface $request,
        ResponseInterface $response,
        Exception $reason = null): void;
}
