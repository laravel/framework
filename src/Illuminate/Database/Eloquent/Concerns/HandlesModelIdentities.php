<?php

declare(strict_types=1);

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\IdentityManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelIdentityException;
use Illuminate\Support\Facades\Date;

trait HandlesModelIdentities
{
    /**
     * Indicates whether this model should be stored by identity.
     *
     * @var bool
     */
    protected $identifiable = false;

    /**
     * @var IdentityManager|null
     */
    private $identityManager;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootHandlesModelIdentities()
    {
        static::deleted(function (Model $model) {
            $model->forgetModelIdentity();
        });

        static::created(function (Model $model) {
            $model->storeModelIdentity();
        });
    }

    /**
     * Get the identifier for the model.
     *
     * @throws \Illuminate\Database\Eloquent\ModelIdentityException
     *
     * @return string
     */
    public function getModelIdentifier()
    {
        if (! $this->isIdentifiableModel()) {
            throw ModelIdentityException::forModel($this);
        }

        return implode(':', [
            $this->getConnection()->getName(),
            static::class,
            $this->getKey()
        ]);
    }

    /**
     * Determine if this model is identifiable.
     *
     * @return bool
     */
    public function isIdentifiableModel()
    {
        return $this->identifiable;
    }

    /**
     * Store this model in the identity manager.
     *
     * @return void
     */
    public function storeModelIdentity()
    {
        $this->getIdentityManager()->storeModel($this);
    }

    /**
     * Forget this models identity.
     *
     * @return void
     */
    public function forgetModelIdentity()
    {
        $this->getIdentityManager()->forgetModel($this);
    }

    /**
     * Get the identity manager.
     *
     * @return \Illuminate\Database\Eloquent\IdentityManager
     */
    private function getIdentityManager()
    {
        if (! isset($this->identityManager)) {
            $this->identityManager = app(IdentityManager::class);
        }

        return $this->identityManager;
    }

    /**
     * Determine whether the provided attributes are newer than the provided models attributes.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array                               $attributes
     *
     * @return bool
     */
    protected function areAttributesMoreRecent(Model $model, array $attributes)
    {
        if (! $this->exists || ! $this->usesTimestamps()) {
            return true;
        }

        $updatedAt = $attributes[$this->getUpdatedAtColumn()] ?? null;

        if ($updatedAt !== null) {
            $format = $this->getDateFormat();

            if (is_numeric($updatedAt)) {
                $updatedAt = Date::createFromTimestamp($updatedAt);
            } else if (Date::hasFormat($updatedAt, $format)) {
                $updatedAt = Date::createFromFormat($format, $updatedAt);
            }
        }

        $modelUpdatedAt = $model->getAttribute($this->getUpdatedAtColumn());

        return $modelUpdatedAt === null || $updatedAt === null || $modelUpdatedAt->isBefore($updatedAt);
    }
}
