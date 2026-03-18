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

        if (is_null($this->model->{$options->to})) {
            $this->model->{$options->to} = $this->generate();
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

        $this->model->{$options->to} = $this->generate();
    }

    /**
     * Generate a slug for the model.
     *
     * @throws CouldNotGenerateSlugException
     */
    public function generate(): string
    {
        $sourceValue = $this->resolveSourceValue();
        $slug = $this->slugify($sourceValue);

        if ($slug === '') {
            $this->throwEmptySlugException($sourceValue);
        }

        return $this->ensureUnique($slug);
    }

    /**
     * Throw an exception when the slug source produces an empty slug.
     *
     * @throws CouldNotGenerateSlugException
     */
    protected function throwEmptySlugException(string $sourceValue): void
    {
        $options = $this->options();
        $from = Arr::wrap($options->from);
        $errorKey = $options->errorKey ?? $from[0];
        $columns = implode(', ', $from);

        $exception = CouldNotGenerateSlugException::withMessages([
            $errorKey => $this->resolveErrorMessage($options, 'validation.slug_required'),
        ]);

        $message = 'No slug could be generated for model ['.get_class($this->model)."] using column(s) [{$columns}] with value [{$sourceValue}].";

        (fn () => $this->message = $message)->call($exception);

        throw $exception;
    }

    /**
     * Resolve the user-facing error message for a failed slug generation.
     */
    protected function resolveErrorMessage(Sluggable $options, string $translationKey): string
    {
        $from = array_map(fn (string $name) => str_replace('_', ' ', Str::snake($name)), Arr::wrap($options->from));
        $attribute = count($from) === 1 ? $from[0] : implode(' and ', [implode(', ', array_slice($from, 0, -1)), end($from)]);
        $replacements = ['attribute' => $attribute, 'slug' => str_replace('_', ' ', Str::snake($options->to))];

        return $options->errorMessage
            ? __($options->errorMessage, $replacements)
            : __($translationKey, $replacements);
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
        return $this->model->isDirty($this->options()->to);
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

        $value = str_replace("'", '', $value);

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
                $from = Arr::wrap($options->from);
                $errorKey = $options->errorKey ?? $from[0];
                $columns = implode(', ', $from);

                $exception = CouldNotGenerateSlugException::withMessages([
                    $errorKey => $this->resolveErrorMessage($options, 'validation.slug_unique'),
                ]);

                $message = 'No unique slug could be generated for model ['.get_class($this->model)."] using column(s) [{$columns}] with value [{$originalSlug}] after {$options->maxAttempts} attempts.";

                (fn () => $this->message = $message)->call($exception);

                throw $exception;
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

        $query->where($options->to, $slug);

        foreach (Arr::wrap($options->scope) as $column) {
            $query->where($column, $model->{$column});
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        return $query->exists();
    }
}
