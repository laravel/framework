<?php

namespace Illuminate\Services;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Services\Attributes\RemoteName;
use Illuminate\Services\Attributes\Service;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use LogicException;
use ReflectionClass;
use Throwable;

trait InteractsWithRemoteWorker
{
    /**
     * The callbacks to run once the service responds.
     *
     * @var array{then: array, catch: array}
     */
    protected $remoteCallbacks = ['then' => [], 'catch' => []];

    /**
     * Call the service now and return its decoded JSON response.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     *
     * @throws \Illuminate\Services\RemoteJobFailed
     */
    public static function call(...$arguments)
    {
        $job = new static(...$arguments);

        $response = $job->sendToService();

        if ($response->failed()) {
            throw RemoteJobFailed::fromResponse($job->remoteName(), $response);
        }

        return $response->json();
    }

    /**
     * Send the job to the service from the queue worker.
     *
     * @return void
     */
    public function handle()
    {
        $response = $this->sendToService();

        // The service rejected the request itself, so retrying it would fail the same way...
        if ($response->clientError()) {
            $this->fail(RemoteJobFailed::fromResponse($this->remoteName(), $response));

            return;
        }

        $result = $response->throw()->json();

        foreach ($this->remoteCallbacks['then'] as $callback) {
            $callback($result);
        }
    }

    /**
     * Run the "catch" callbacks once the job has failed.
     *
     * @param  \Throwable|null  $exception
     * @return void
     */
    public function failed(?Throwable $exception = null)
    {
        if (! $exception instanceof RemoteJobFailed) {
            $exception = new RemoteJobFailed(
                $this->remoteName(),
                $exception?->getMessage() ?? "Remote job [{$this->remoteName()}] failed.",
                previous: $exception,
            );
        }

        foreach ($this->remoteCallbacks['catch'] as $callback) {
            $callback($exception);
        }
    }

    /**
     * Add a callback to run with the service's response.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function then($callback)
    {
        return $this->addRemoteCallback('then', $callback);
    }

    /**
     * Add a callback to run when the service fails the job.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function catch($callback)
    {
        return $this->addRemoteCallback('catch', $callback);
    }

    /**
     * Get the name the service knows this job by, which is also the request path.
     *
     * @return string
     */
    public function remoteName()
    {
        return $this->classAttribute(RemoteName::class)?->name
            ?? Str::snake(class_basename($this));
    }

    /**
     * Get the data sent to the service, which defaults to the job's constructor arguments.
     *
     * @return mixed
     */
    public function remoteData()
    {
        if (method_exists($this, 'toPayload')) {
            return $this->toPayload();
        }

        $parameters = (new ReflectionClass($this))->getConstructor()?->getParameters() ?? [];

        $data = [];

        foreach ($parameters as $parameter) {
            $data[$parameter->getName()] = $this->{$parameter->getName()} ?? null;
        }

        return $data;
    }

    /**
     * Get the name of the service that handles this job.
     *
     * @return string
     *
     * @throws \LogicException
     */
    public function remoteService()
    {
        return $this->classAttribute(Service::class)?->name
            ?? throw new LogicException(sprintf('Remote job [%s] must declare its service using the #[Service] attribute.', static::class));
    }

    /**
     * Send the job's data to its service.
     *
     * @return \Illuminate\Http\Client\Response
     */
    protected function sendToService()
    {
        return Container::getInstance()->make(ServiceManager::class)
            ->http($this->remoteService())
            ->when(isset($this->job) ? $this->job->uuid() : null, fn ($request, $uuid) => $request->withHeaders(['Idempotency-Key' => $uuid]))
            ->post($this->remoteName(), $this->remoteData());
    }

    /**
     * Get an instance of the given attribute from this job's class.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return TAttribute|null
     */
    protected function classAttribute(string $attribute)
    {
        return ((new ReflectionClass($this))->getAttributes($attribute)[0] ?? null)?->newInstance();
    }

    /**
     * Register a remote callback with proper serialization.
     *
     * @param  string  $type
     * @param  callable  $callback
     * @return $this
     */
    protected function addRemoteCallback(string $type, $callback)
    {
        $this->remoteCallbacks[$type][] = $callback instanceof Closure
            ? new SerializableClosure($callback)
            : $callback;

        return $this;
    }
}
