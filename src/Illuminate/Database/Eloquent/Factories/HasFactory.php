<?php

namespace Illuminate\Database\Eloquent\Factories;

use Closure;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 *
 * @phpstan-require-extends Model
 */
trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  (callable(array<string, mixed>, static|null): array<string, mixed>)|array<string, mixed>|int|null  $count
     * @param  (callable(array<string, mixed>, static|null): array<string, mixed>)|array<string, mixed>  $state
     * @return TFactory
     */
    public static function factory($count = null, $state = [])
    {
        $factory = static::newFactory() ?? Factory::factoryForModel(static::class);

        return $factory
            ->count(is_numeric($count) ? $count : null)
            ->state(is_callable($count) || is_array($count) ? $count : $state);
    }

    /**
     * Returns a random record from the database or the factory if there are no records.
     *
     * @param  (Closure(Builder<static>): mixed)|null  $query
     * @param  (Closure(TFactory): TFactory)|null  $factory
     * @return ($count is null ? static|TFactory : Collection<int, static>|TFactory)
     *
     * @phpstan-ignore method.childParameterType (parameters are not covariant)
     */
    public static function randomOrFactory(?Closure $query = null, ?Closure $factory = null, ?int $count = null): static|Collection|Factory
    {
        $factory ??= fn ($factory) => $factory;

        return static::query()
            ->inRandomOrder()
            /** @phpstan-ignore argument.type (unknown builder) */
            ->when($query, $query)
            ->limit($count)
            ->get()
            ->whenNotEmpty(
                fn ($m) => $count === null ? $m->first() : $m,
                fn () => $factory(static::factory($count)),
            );
    }

    /**
     * Returns a random record from the database or create one with the factory if there are no records.
     *
     * @param  (Closure(Builder<static>): mixed)|null  $query
     * @param  (Closure(TFactory): TFactory)|null  $factory
     * @return ($count is null ? static : Collection<int, static>)
     *
     * @phpstan-ignore method.childParameterType (parameters are not covariant)
     */
    public static function randomOrFactoryCreate(?Closure $query = null, ?Closure $factory = null, ?int $count = null): static|Collection
    {
        $model = static::randomOrFactory($query, $factory, $count);

        if (! $model instanceof Factory) {
            return $model;
        }

        /** @phpstan-ignore return.type (returns static) */
        return $count === null ? $model->createOne() : $model->createMany();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TFactory|null
     */
    protected static function newFactory()
    {
        if (isset(static::$factory)) {
            return static::$factory::new();
        }

        return static::getUseFactoryAttribute() ?? null;
    }

    /**
     * Get the factory from the UseFactory class attribute.
     *
     * @return TFactory|null
     */
    protected static function getUseFactoryAttribute()
    {
        $attributes = (new \ReflectionClass(static::class))
            ->getAttributes(UseFactory::class);

        if ($attributes !== []) {
            $useFactory = $attributes[0]->newInstance();

            $factory = $useFactory->factoryClass::new();

            $factory->guessModelNamesUsing(fn () => static::class);

            return $factory;
        }
    }
}
