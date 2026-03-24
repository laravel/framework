<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\AttributeBasedPostResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\AttributeBasedUserResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\ConfigureOverridesAttributeResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\MethodOverridesAttributeResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Profile;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\PropertyOverridesAttributeResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\ResourceWithCustomWrap;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\ResourceWithJsonApiInfo;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\ResourceWithMetaAndLinks;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\User;

class JsonApiResourceAttributeTest extends TestCase
{
    #[\Override]
    protected function defineRoutes($router)
    {
        parent::defineRoutes($router);

        $router->get('attribute-users/{userId}', function ($userId) {
            return new AttributeBasedUserResource(User::find($userId));
        });

        $router->get('attribute-posts/{postId}', function ($postId) {
            return new AttributeBasedPostResource(Post::find($postId));
        });

        $router->get('meta-links-users/{userId}', function ($userId) {
            return new ResourceWithMetaAndLinks(User::find($userId));
        });

        $router->get('jsonapi-info-users/{userId}', function ($userId) {
            return new ResourceWithJsonApiInfo(User::find($userId));
        });

        $router->get('custom-wrap-users/{userId}', function ($userId) {
            return new ResourceWithCustomWrap(User::find($userId));
        });

        $router->get('property-override-users/{userId}', function ($userId) {
            return new PropertyOverridesAttributeResource(User::find($userId));
        });

        $router->get('method-override-users/{userId}', function ($userId) {
            return new MethodOverridesAttributeResource(User::find($userId));
        });

        $router->get('configure-override-users/{userId}', function ($userId) {
            ConfigureOverridesAttributeResource::configure('2.0', ['bulk'], ['https://example.com/profiles/admin']);

            return new ConfigureOverridesAttributeResource(User::find($userId));
        });

        $router->get('jsonapi-info-users-collection', function () {
            return ResourceWithJsonApiInfo::collection(User::paginate(5));
        });
    }

    public function testAttributeBasedResourceResolvesAttributes()
    {
        $user = User::factory()->create();

        $this->getJson("/attribute-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'attribute_based_users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    public function testAttributeBasedResourceResolvesRelationships()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson("/attribute-posts/{$post->getKey()}?".http_build_query(['include' => 'author']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('data.id', (string) $post->getKey())
            ->assertJsonPath('data.type', 'attribute_based_posts')
            ->assertJsonPath('data.attributes.title', $post->title)
            ->assertJsonPath('data.attributes.content', $post->content)
            ->assertJsonPath('data.relationships.author.data.id', (string) $user->getKey())
            ->assertJsonPath('data.relationships.author.data.type', 'authors')
            ->assertJsonCount(1, 'included');
    }

    public function testPropertyOverridesPhpAttribute()
    {
        $user = User::factory()->create();

        // The PropertyOverridesAttributeResource has both #[Attributes(['name'])] and
        // protected array $attributes = ['name', 'email']. The property should win.
        $this->getJson("/property-override-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'property_overrides_attributes',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    public function testMethodOverrideOverridesPhpAttribute()
    {
        $user = User::factory()->create();

        // The MethodOverridesAttributeResource has #[Attributes(['name'])] and
        // a toAttributes() override returning name + email. The method should win.
        $this->getJson("/method-override-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'method_overrides_attributes',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    public function testJsonApiMetaAttribute()
    {
        $user = User::factory()->create();

        $this->getJson("/meta-links-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('data.meta.copyright', '2024 Laravel');
    }

    public function testJsonApiLinksAttribute()
    {
        $user = User::factory()->create();

        $this->getJson("/meta-links-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('data.links.self', 'https://example.com/users/1');
    }

    public function testJsonApiInformationAttribute()
    {
        $user = User::factory()->create();

        $this->getJson("/jsonapi-info-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.0')
            ->assertJsonPath('jsonapi.ext', ['atomic'])
            ->assertJsonPath('jsonapi.profile', ['https://example.com/profiles/blog']);
    }

    public function testJsonApiInformationAttributeOnCollection()
    {
        User::factory()->create();

        $this->getJson('/jsonapi-info-users-collection')
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.0')
            ->assertJsonPath('jsonapi.ext', ['atomic'])
            ->assertJsonPath('jsonapi.profile', ['https://example.com/profiles/blog']);
    }

    public function testWrapsAttribute()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/custom-wrap-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json');

        $response->assertJsonStructure(['result']);
        $response->assertJsonMissing(['data' => []]);
        $response->assertJsonPath('result.id', (string) $user->getKey());
        $response->assertJsonPath('result.type', 'resource_with_custom_wraps');
    }

    public function testConfigureOverridesJsonApiInformationAttribute()
    {
        $user = User::factory()->create();

        // The route calls configure('2.0', ['bulk'], [...]) before returning the resource.
        // configure() should win over #[JsonApiInformation(version: '1.0', ...)].
        $this->getJson("/configure-override-users/{$user->getKey()}")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '2.0')
            ->assertJsonPath('jsonapi.ext', ['bulk'])
            ->assertJsonPath('jsonapi.profile', ['https://example.com/profiles/admin']);
    }

    public function testFlushStateClearsAttributeCache()
    {
        $user = User::factory()->create();

        // Trigger attribute resolution to populate cache
        $this->getJson("/attribute-users/{$user->getKey()}");

        // Flush state should clear the cache
        JsonApiResource::flushState();

        // Verify we can resolve again (cache was cleared, not stale)
        $this->getJson("/attribute-users/{$user->getKey()}")
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'attribute_based_users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }
}
