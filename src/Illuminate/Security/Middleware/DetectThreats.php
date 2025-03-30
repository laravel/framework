<?php

namespace Illuminate\Security\Middleware;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Security\Events\ThreatDetected;
use Illuminate\Security\IdsManager;
use Symfony\Component\HttpFoundation\Response;

class DetectThreats
{
    /**
     * The IDS manager instance.
     *
     * @var \Illuminate\Security\IdsManager
     */
    protected $ids;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The paths that should be excluded from analysis.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Security\IdsManager  $ids
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     * @return void
     */
    public function __construct(IdsManager $ids, ?Dispatcher $events = null)
    {
        $this->ids = $ids;
        $this->events = $events;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->shouldBeAnalyzed($request)) {
            return $next($request);
        }

        $isUnderAttack = $this->ids->analyze($request);

        if ($isUnderAttack) {
            $this->reportThreat($request);

            return $this->handleDetectedThreat($request);
        }

        return $next($request);
    }

    /**
     * Determine if the request should be analyzed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldBeAnalyzed($request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Report the detected threat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function reportThreat($request): void
    {
        if ($this->events) {
            foreach ($this->ids->getDetectedThreats() as $threat) {
                $this->events->dispatch(new ThreatDetected($threat, $request));
            }
        }

        // Here you could add additional reporting like logging or alerting
    }

    /**
     * Handle the request with a detected threat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleDetectedThreat($request)
    {
        // You can customize the response, e.g. a 403 error or custom error page
        return new Response('Forbidden: Security threat detected', 403);
    }
}
