<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Tests\App\Models\Relationships\WithoutTouchingArticle as Article;
use Illuminate\Tests\App\Models\Relationships\WithoutTouchingUser as User;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithoutTouchingTest extends TestCase
{
    public function testItWillNotTouchRelatedModelsWhenUpdatingChild(): void
    {
        /** @var Article $related */
        $related = Mockery::mock(Article::class)->makePartial();
        $related->shouldReceive('getUpdatedAtColumn')->never();
        $related->shouldReceive('freshTimestampString')->never();

        $this->assertFalse($related::isIgnoringTouch());

        Model::withoutTouching(function () use ($related) {
            $this->assertTrue($related::isIgnoringTouch());

            $builder = Mockery::mock(Builder::class);
            $builder->expects('join');
            $parent = Mockery::mock(User::class);

            $parent->expects('getAttribute')->with('id')->andReturn(1);
            $builder->expects('getModel')->andReturn($related);
            $builder->expects('where');
            $builder->expects('getQuery')->times(2)->andReturn(
                Mockery::mock(QueryBuilder::class, ['getGrammar' => Mockery::mock(Grammar::class, ['isExpression' => false])])
            );
            $relation = new BelongsToMany($builder, $parent, 'article_users', 'user_id', 'article_id', 'id', 'id');
            $builder->shouldReceive('update')->never();

            $relation->touch();
        });

        $this->assertFalse($related::isIgnoringTouch());
    }
}
