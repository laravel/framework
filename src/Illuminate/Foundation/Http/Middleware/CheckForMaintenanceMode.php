<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\IpUtils;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;

class CheckForMaintenanceMode
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->isDownForMaintenance()) {
            $data = json_decode(file_get_contents($this->app->storagePath().'/framework/down'), true);

            $allowedIP = $this->getAllowedIPAddress($data);

            if (! empty($allowedIP) && IpUtils::checkIp($request->ip(), $allowedIP)) {
                return $next($request);
            }

            throw new MaintenanceModeException($data['time'], $data['retry'], $data['message']);
        }

        return $next($request);
    }

    /**
     * Get list of allowed IP addresses.
     *
     * @param array $data
     *
     * @return array
     */
    protected function getAllowedIPAddress($data)
    {
        if (empty($data['allowed'])) {
            return [];
        }

        $pattern = '/[,|\n]/';

        if (is_file($data['allowed']) && file_exists($data['allowed'])) {
            $data['allowed'] = preg_split($pattern, file_get_contents($data['allowed']));
        } elseif (preg_match($pattern, $data['allowed'])) {
            $data['allowed'] = preg_split($pattern, $data['allowed']);
        } else {
            $data['allowed'] = (array) $data['allowed'];
        }

        return $data['allowed'];
    }
}
