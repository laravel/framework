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
    protected static $identityManager;

    /**
     * Set the identity manager instance.
     *
     * @param  \Illuminate\Database\Eloquent\IdentityManager  $identityManager
     * @return static
     */
    public static function setIdentityManager(IdentityManager $identityManager)
    {
        static::$identityManager = $identityManager;
    }

    /**
     * Get the identifier for the model.
     *
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\ModelIdentityException
     */
    public function getModelIdentifier()
    {
        if (! $this->isIdentifiableModel()) {
            throw ModelIdentityException::forModel($this);
        }

        return implode(':', [
            $this->getConnection()->getName(),
            static::class,
            $this->getKey(),
        ]);
    }

    /**
     * Determine if this model is identifiable.
     *
     * @return bool
     */
    public function isIdentifiableModel()
    {
        return static::$identityManager !== null && $this->identifiable;
    }

    /**
     * Store this model in the identity manager.
     *
     * @return void
     */
    public function storeModelIdentity()
    {
        if (! isset(static::$identityManager)) {
            return;
        }

        static::$identityManager->storeModel($this);
    }

    /**
     * Forget this models identity.
     *
     * @return void
     */
    public function forgetModelIdentity()
    {
        if (! isset(static::$identityManager)) {
            return;
        }

        static::$identityManager->forgetModel($this);
    }

    /**
     * Determine whether the provided attributes are newer than the provided models attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $attributes
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
            } elseif (Date::hasFormat($updatedAt, $format)) {
                $updatedAt = Date::createFromFormat($format, $updatedAt);
            }
        }

        $modelUpdatedAt = $model->getAttribute($this->getUpdatedAtColumn());

        return $modelUpdatedAt === null || $updatedAt === null || $modelUpdatedAt->isBefore($updatedAt);
    }
}
