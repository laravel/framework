<?php

namespace Illuminate\Security;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IdsManager
{
    /**
     * The registered sensors.
     *
     * @var array
     */
    protected $sensors = [];

    /**
     * The detected threats.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $detectedThreats;

    /**
     * The threat threshold level.
     *
     * @var int
     */
    protected $threshold = 5;

    /**
     * The application container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new IDS manager instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->detectedThreats = new Collection;
    }

    /**
     * Add a sensor to the list of sensors.
     *
     * @param  string|\Illuminate\Security\IdsSensor  $sensor
     * @return $this
     */
    public function addSensor($sensor)
    {
        if (is_string($sensor)) {
            $sensor = $this->container->make($sensor);
        }

        $this->sensors[$sensor->getName()] = $sensor;

        return $this;
    }

    /**
     * Analyze a request with all sensors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function analyze(Request $request): bool
    {
        $this->detectedThreats = new Collection;

        foreach ($this->sensors as $sensor) {
            if ($sensor->detect($request)) {
                $this->detectedThreats->push([
                    'sensor' => $sensor->getName(),
                    'description' => $sensor->getDescription(),
                    'weight' => $sensor->getWeight(),
                    'detected_at' => now(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'request_id' => (string) Str::uuid(),
                ]);
            }
        }

        return $this->getThreatScore() >= $this->threshold;
    }

    /**
     * Set the threat threshold level.
     *
     * @param  int  $threshold
     * @return $this
     */
    public function setThreshold(int $threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Get the total threat score.
     *
     * @return int
     */
    public function getThreatScore(): int
    {
        return $this->detectedThreats->sum('weight');
    }

    /**
     * Get all detected threats.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDetectedThreats(): Collection
    {
        return $this->detectedThreats;
    }

    /**
     * Get all registered sensors.
     *
     * @return array
     */
    public function getSensors(): array
    {
        return $this->sensors;
    }
}
