<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Sluggable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Create a new slug generator instance.
     */
    public function __construct(
        protected Model $model,
    ) {
    }

    /**
     * Handle the model creating event.
     */
    public function handleCreating(): void
    {
        $options = $this->options();

        if (is_null($this->model->{$options->column})) {
            $this->model->{$options->column} = $this->generate();
        }
    }

    /**
     * Handle the model updating event.
     */
    public function handleUpdating(): void
    {
        $options = $this->options();

        if (! $options->onUpdating) {
            return;
        }

        if ($this->hasCustomSlugBeenUsed() || ! $this->sourceHasChanged()) {
            return;
        }

        $this->model->{$options->column} = $this->generate();
    }

    /**
     * Generate a slug for the model.
     *
     * @throws CouldNotGenerateSlugException
     */
    public function generate(): string
    {
        $slug = $this->slugify($this->resolveSourceValue());

        if ($slug === '') {
            throw new CouldNotGenerateSlugException(
                'Could not generate a slug for ['.get_class($this->model).'] from the given source value.'
            );
        }

        return $this->ensureUnique($slug);
    }

    /**
     * Determine if the slug source columns have changed.
     */
    protected function sourceHasChanged(): bool
    {
        return collect(Arr::wrap($this->options()->from))
            ->contains(fn (string $column) => $this->model->isDirty($column));
    }

    /**
     * Determine if the user manually changed the slug field.
     */
    protected function hasCustomSlugBeenUsed(): bool
    {
        return $this->model->isDirty($this->options()->column);
    }

    /**
     * Get the sluggable attribute from the model.
     */
    protected function options(): Sluggable
    {
        return $this->model::resolveClassAttribute(Sluggable::class);
    }

    /**
     * Get the source value to generate the slug from.
     */
    protected function resolveSourceValue(): string
    {
        return collect(Arr::wrap($this->options()->from))
            ->map(fn (string $column) => $this->model->{$column})
            ->implode(' ');
    }

    /**
     * Convert a string to a slug.
     */
    protected function slugify(string $value): string
    {
        $options = $this->options();
        $separator = $options->separator;
        $flip = $separator === '-' ? '_' : '-';

        $value = Str::transliterate($value, unknown: '');

        $value = preg_replace('!['.preg_quote($flip).']+!u', $separator, $value);

        $value = preg_replace('![^'.preg_quote($separator).'\pL\pN\s.]+!u', $separator, mb_strtolower($value, 'UTF-8'));

        $value = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $value);

        $value = preg_replace('!'.preg_quote($separator).'*\.'.preg_quote($separator).'*!u', '.', $value);

        $value = preg_replace('!\.+!u', '.', $value);

        $value = trim($value, $separator.'.');

        if ($options->maxLength) {
            $value = rtrim(mb_substr($value, 0, $options->maxLength), $separator.'.');
        }

        return $value;
    }

    /**
     * Ensure the slug is unique by appending a numeric suffix if needed.
     *
     * @throws CouldNotGenerateSlugException
     */
    protected function ensureUnique(string $slug): string
    {
        $options = $this->options();

        if (! $options->unique) {
            return $slug;
        }

        $originalSlug = $slug;
        $count = 1;

        while ($this->slugAlreadyExists($slug)) {
            $count++;

            if ($count > $options->maxAttempts) {
                throw new CouldNotGenerateSlugException(
                    'Could not generate a unique slug for ['.get_class($this->model)."] with base [{$originalSlug}] after {$options->maxAttempts} attempts."
                );
            }

            $suffix = $options->separator.$count;

            if ($options->maxLength) {
                $slug = rtrim(mb_substr($originalSlug, 0, max(0, $options->maxLength - mb_strlen($suffix))), $options->separator).$suffix;
            } else {
                $slug = $originalSlug.$suffix;
            }
        }

        return $slug;
    }

    /**
     * Determine if the given slug already exists.
     */
    protected function slugAlreadyExists(string $slug): bool
    {
        $options = $this->options();
        $model = $this->model;

        $query = $model::withoutGlobalScopes();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withTrashed();
        }

        $query->where($options->column, $slug);

        foreach (Arr::wrap($options->scope) as $column) {
            $query->where($column, $model->{$column});
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        return $query->exists();
    }
}
