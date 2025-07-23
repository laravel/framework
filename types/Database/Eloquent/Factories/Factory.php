<?php

use Illuminate\Database\Eloquent\Factories\Factory;

use function PHPStan\Testing\assertType;

/** @extends Illuminate\Database\Eloquent\Factories\Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}

/** @extends Illuminate\Database\Eloquent\Factories\Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}

assertType('UserFactory', $factory = UserFactory::new());
assertType('UserFactory', UserFactory::new(['string' => 'string']));
assertType('UserFactory', UserFactory::new(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('array<string, mixed>', $factory->definition());

assertType('UserFactory', $factory::times(10));

assertType('UserFactory', $factory->configure());

assertType('array<int|string, mixed>', $factory->raw());
assertType('array<int|string, mixed>', $factory->raw(['string' => 'string']));
assertType('array<int|string, mixed>', $factory->raw(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('User', $factory->createOne());
assertType('User', $factory->createOne(['string' => 'string']));
assertType('User', $factory->createOne(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('User', $factory->createOneQuietly());
assertType('User', $factory->createOneQuietly(['string' => 'string']));
assertType('User', $factory->createOneQuietly(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('Illuminate\Database\Eloquent\Collection<int, User>', $factory->createMany([['string' => 'string']]));
assertType('Illuminate\Database\Eloquent\Collection<int, User>', $factory->createMany(3));
assertType('Illuminate\Database\Eloquent\Collection<int, User>', $factory->createMany());

assertType('Illuminate\Database\Eloquent\Collection<int, User>', $factory->createManyQuietly([['string' => 'string']]));
assertType('Illuminate\Database\Eloquent\Collection<int, User>', $factory->createManyQuietly(3));
assertType('Illuminate\Database\Eloquent\Collection<int, User>', $factory->createManyQuietly());

assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->create());
assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->create(['string' => 'string']));
assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->create(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->createQuietly());
assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->createQuietly(['string' => 'string']));
assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->createQuietly(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('Closure(): (Illuminate\Database\Eloquent\Collection<int, User>|User)', $factory->lazy());
assertType('Closure(): (Illuminate\Database\Eloquent\Collection<int, User>|User)', $factory->lazy(['string' => 'string']));

assertType('User', $factory->makeOne());
assertType('User', $factory->makeOne(['string' => 'string']));
assertType('User', $factory->makeOne(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->make());
assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->make(['string' => 'string']));
assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $factory->make(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));

assertType('UserFactory', $factory->state(['string' => 'string']));
assertType('UserFactory', $factory->state(function ($attributes) {
    assertType('array<string, mixed>', $attributes);

    return ['string' => 'string'];
}));
assertType('UserFactory', $factory->state(function ($attributes, $model) {
    assertType('array<string, mixed>', $attributes);
    assertType('Illuminate\Database\Eloquent\Model|null', $model);

    return ['string' => 'string'];
}));

assertType('UserFactory', $factory->sequence([['string' => 'string']]));

assertType('UserFactory', $factory->has($factory));

assertType('UserFactory', $factory->hasAttached($factory, ['string' => 'string']));
assertType('UserFactory', $factory->hasAttached($factory->createOne(), ['string' => 'string']));
assertType('UserFactory', $factory->hasAttached($factory->createOne(), function () {
    return ['string' => 'string'];
}));

assertType('UserFactory', $factory->for($factory));
assertType('UserFactory', $factory->for($factory->createOne()));

assertType('UserFactory', $factory->afterMaking(function ($user) {
    assertType('User', $user);

    return 'string';
}));

assertType('UserFactory', $factory->afterCreating(function ($user) {
    assertType('User', $user);

    return 'string';
}));

assertType('UserFactory', $factory->count(10));

assertType('UserFactory', $factory->connection('string'));

assertType('User', $factory->newModel());
assertType('User', $factory->newModel(['string' => 'string']));

assertType('class-string<User>', $factory->modelName());

assertType('Post|null', $factory->getRandomRecycledModel(Post::class));

Factory::guessModelNamesUsing(function (Factory $factory) {
    return match (true) {
        $factory instanceof UserFactory => User::class,
        default => throw new LogicException('Unknown factory'),
    };
});

$factory->useNamespace('string');

assertType('Illuminate\Database\Eloquent\Factories\Factory<User>', $factory::factoryForModel(User::class));
assertType('class-string<Illuminate\Database\Eloquent\Factories\Factory<User>>', $factory->resolveFactoryName(User::class));

Factory::guessFactoryNamesUsing(function (string $modelName) {
    return match ($modelName) {
        User::class => UserFactory::class,
        default => throw new LogicException('Unknown factory'),
    };
});

UserFactory::new()->has(
    PostFactory::new()
        ->state(function ($attributes, $user) {
            assertType('array<string, mixed>', $attributes);
            assertType('Illuminate\Database\Eloquent\Model|null', $user);

            return ['user_id' => $user?->getKey()];
        })
        ->prependState(function ($attributes, $user) {
            assertType('array<string, mixed>', $attributes);
            assertType('Illuminate\Database\Eloquent\Model|null', $user);

            return ['user_id' => $user?->getKey()];
        }),
);
