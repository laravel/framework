<?php

namespace Illuminate\Queue;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait SerializesModels
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * Indicates that the job should be deleted for missing models.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = false;

    /**
     * Indicates that the job should fail for missing models.
     *
     * @var bool
     */
    protected $failWhenMissingModels = true;

    /**
     * Indicate that the job should be deleted for missing models.
     *
     * @var bool
     */
    public function deleteWhenMissingModels()
    {
        $this->deleteWhenMissingModels = true;
        $this->failWhenMissingModels = false;

        return $this;
    }

    /**
     * Indicate that the job should fail for missing models.
     *
     * @var bool
     */
    public function failWhenMissingModels()
    {
        $this->deleteWhenMissingModels = false;
        $this->failWhenMissingModels = true;

        return $this;
    }

    /**
     * Indicate that the job should ignore missing models.
     *
     * @var bool
     */
    public function continueWhenMissingModels()
    {
        $this->deleteWhenMissingModels = false;
        $this->failWhenMissingModels = false;

        return $this;
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            $property->setValue($this, $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)
            ));
        }

        return array_map(function ($p) {
            return $p->getName();
        }, $properties);
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            try {
                $property->setValue($this, $this->getRestoredPropertyValue(
                    $this->getPropertyValue($property)
                ));
            } catch (ModelNotFoundException $e) {
                if (isset($this->deleteWhenMissingModels) && $this->deleteWhenMissingModels) {
                    return $this->delete();
                }

                if (isset($this->failWhenMissingModels) && $this->failWhenMissingModels) {
                    return $this->fail($e);
                }

                throw $e;
            }
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
