<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentJoinsModelsTest extends TestCase
{
    private DB $db;

    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
        $this->db = $db;
    }
    protected function tearDown(): void
    {
        \Mockery::close();
    }
    public function testJoinMany()
    {
        $mock = \Mockery::mock(Builder::class, [$this->db->getDatabaseManager()->query()])->makePartial();
        $mock->shouldReceive('join')->withSomeOfArgs('comments')->andReturn($mock)->once();
        $query =$mock->setModel(new Blog());
        $query->joinMany(Comment::class);
        \Mockery::close();
    }

    public function testJoinOne()
    {
        $mock = \Mockery::mock(Builder::class, [$this->db->getDatabaseManager()->query()])->makePartial();
        $mock->shouldReceive('join')->withSomeOfArgs('blogs')->andReturn($mock)->once();
        $query = $mock->setModel(new Comment());
        $query->joinOne(Blog::class);
        \Mockery::close();
    }

    public function testSimpleHasMany()
    {
        $blog = new Blog();
        $query = $blog->newQuery()->joinMany(Comment::class)->toSql();
        $this->assertSame('select * from "blogs" inner join "comments" on "comments"."blog_id" = "blogs"."id"', $query);
    }

    public function testSimpleHasOne()
    {
        $query = (new Comment())->newQuery()->joinOne(Blog::class)->toSql();
        $this->assertSame('select * from "comments" inner join "blogs" on "blogs"."id" = "comments"."blog_id"', $query);
    }

    public function testSimpleHasManyAlternativePrimaryKeyName()
    {
        $blog = new Alternative();
        $query = $blog->newQuery()->joinMany(Blog::class)->toSql();
        $this->assertSame('select * from "alternatives" inner join "blogs" on "blogs"."alternative_key" = "alternatives"."key"', $query);
    }

    public function testIncludeScopesInJoin()
    {
        $blog = new Blog();
        $query = $blog->newQuery()->joinMany(DeletableComment::class)->toSql();
        $this->assertSame('select * from "blogs" inner join "deletable_comments" on "deletable_comments"."blog_id" = "blogs"."id" and ("deletable_comments"."deleted_at" is null)', $query);
    }

    public function testCanJoinBuilder()
    {
        $blog = new Blog();
        $query = $blog->newQuery()->joinMany(DeletableComment::withTrashed())->toSql();
        $this->assertSame('select * from "blogs" inner join "deletable_comments" on "deletable_comments"."blog_id" = "blogs"."id"', $query);
    }

    public function testAddWhereStatements()
    {
        $blog = new Blog();
        $query = $blog->newQuery()->joinMany(Comment::query()->whereNull('comments.deleted_at'))->toSql();
        $this->assertSame('select * from "blogs" inner join "comments" on "comments"."blog_id" = "blogs"."id" and ("comments"."deleted_at" is null)', $query);
    }
}


class Blog extends Model {}
class Comment extends Model {}


class DeletableComment extends Model {
    use SoftDeletes;
}

class Alternative extends Model
{
    protected $primaryKey = 'key';
}
