<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HasGeneratedColumns
{
    /**
     * Perform any actions that are necessary after the model is saved.
     *
     * @param  array  $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        // We will reload the current model instance with fresh attributes from the
        // database, making sure it has correct values for all generated columns
        // after the model instance is saved just before firing "saved" event.
        $this->refreshRawAttributes();

        parent::finishSave($options);
    }
}
