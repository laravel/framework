<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Database\ModelIdentifier;
use ReflectionClass;
use ReflectionProperty;

trait SerializesModels
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * A list of serialized model identifiers.
     *
     * @var array
     */
    protected $identifiers = [];

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            if ($property->getName() === 'identifiers') {
                continue;
            }

            $serializedValue = $this->getSerializedPropertyValue(
                $value = $this->getPropertyValue($property)
            );

            if ($serializedValue instanceof ModelIdentifier) {
                $this->identifiers[$property->getName()] = $serializedValue;

                // Set an empty instance of the model or collection to support typed properties...
                $property->setValue($this, new $value);
            } else {
                $property->setValue($this, $value);
            }
        }

        return array_values(array_filter(array_map(function ($p) {
            return $p->isStatic() ? null : $p->getName();
        }, $properties)));
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            if ($property->isStatic() || $property->getName() === 'identifiers') {
                continue;
            }

            if (isset($this->identifiers[$property->getName()])) {
                $value = $this->identifiers[$property->getName()];
            } else {
                $value = $this->getPropertyValue($property);
            }

            $property->setValue($this, $this->getRestoredPropertyValue(
                $value
            ));
        }
    }

    /**
     * Get the property value for the given property.
     *
     * @param  \ReflectionProperty  $property
     * @return mixed
     */
    protected function getPropertyValue(ReflectionProperty $property)
    {
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
