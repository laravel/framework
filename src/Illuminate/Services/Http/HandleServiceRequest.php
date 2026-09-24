<?php

namespace Illuminate\Services\Http;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandleServiceRequest
{
    /**
     * Build the job from the request body, then queue it or run it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $bus
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Container $container, Dispatcher $bus)
    {
        try {
            $job = $container->make($request->route()->defaults['_service_job'], $request->json()->all());
        } catch (BindingResolutionException $e) {
            throw new HttpException(422, $e->getMessage(), $e);
        }

        if ($job instanceof ShouldQueue) {
            $bus->dispatch($job);

            return new JsonResponse(['queued' => true], 202);
        }

        return new JsonResponse($bus->dispatchNow($job));
    }
}
