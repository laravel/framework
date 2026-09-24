<?php

namespace Illuminate\Services;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Services\ShouldRunRemotely;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ServiceRequest implements ShouldRunRemotely
{
    use InteractsWithQueue, InteractsWithRemoteWorker, Queueable, SerializesModels;

    /**
     * Create a new service request instance.
     *
     * @param  string  $service
     * @param  string  $path
     * @param  array  $data
     */
    public function __construct(public string $service, public string $path, public array $data = [])
    {
        //
    }

    /**
     * Get the name of the service that handles this job.
     *
     * @return string
     */
    public function remoteService()
    {
        return $this->service;
    }

    /**
     * Get the name the service knows this job by, which is also the request path.
     *
     * @return string
     */
    public function remoteName()
    {
        return $this->path;
    }

    /**
     * Get the data sent to the service.
     *
     * @return array
     */
    public function remoteData()
    {
        return $this->data;
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return "{$this->service}/{$this->path}";
    }
}
