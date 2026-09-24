<?php

namespace Illuminate\Services;

use Illuminate\Foundation\Bus\PendingDispatch;

class ServiceClient
{
    /**
     * Create a new service client instance.
     *
     * @param  \Illuminate\Services\ServiceManager  $manager
     * @param  string  $service
     */
    public function __construct(protected ServiceManager $manager, protected string $service)
    {
        //
    }

    /**
     * Call the given path on the service now and return its decoded JSON response.
     *
     * @param  string  $path
     * @param  array  $data
     * @return mixed
     *
     * @throws \Illuminate\Services\RemoteJobFailed
     */
    public function call(string $path, array $data = [])
    {
        $response = $this->manager->http($this->service)->post($path, $data);

        if ($response->failed()) {
            throw RemoteJobFailed::fromResponse($path, $response);
        }

        return $response->json();
    }

    /**
     * Queue a call to the given path on the service.
     *
     * @param  string  $path
     * @param  array  $data
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function dispatch(string $path, array $data = [])
    {
        return new PendingDispatch(new ServiceRequest($this->service, $path, $data));
    }

    /**
     * Call the path named after the method, using the arguments as the request body.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->call($method, array_is_list($parameters) ? ($parameters[0] ?? []) : $parameters);
    }
}
