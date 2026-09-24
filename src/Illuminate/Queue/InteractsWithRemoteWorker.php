<?php

namespace Illuminate\Queue;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Queue\Attributes\RemoteName;
use Illuminate\Queue\Attributes\Service;
use Illuminate\Support\Facades\Http;
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
     * @throws \Illuminate\Queue\RemoteJobFailed
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
     * Send the job's data to the configured service.
     *
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \LogicException
     */
    protected function sendToService()
    {
        $service = $this->classAttribute(Service::class)?->name
            ?? throw new LogicException(sprintf('Remote job [%s] must declare its service using the #[Service] attribute.', static::class));

        $config = Container::getInstance()->make('config')->get("services.{$service}", []);

        if (! is_string($url = $config['url'] ?? null) || $url === '') {
            throw new LogicException("Service [{$service}] does not have a URL configured.");
        }

        return Http::baseUrl($url)
            ->acceptJson()
            ->when($config['token'] ?? null, fn ($request, $token) => $request->withToken($token))
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
