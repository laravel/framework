<?php

namespace Illuminate\Security;

use Illuminate\Http\Request;

abstract class IdsSensor
{
    /**
     * The detection weight of this sensor.
     *
     * @var int
     */
    protected $weight = 1;

    /**
     * The description of this sensor.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Detect if this sensor has identified a threat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    abstract public function detect(Request $request): bool;

    /**
     * Get the weight of this sensor.
     *
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * Get the description of this sensor.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the name of the sensor.
     *
     * @return string
     */
    public function getName(): string
    {
        return class_basename($this);
    }
}
