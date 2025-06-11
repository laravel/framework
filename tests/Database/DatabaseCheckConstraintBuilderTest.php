<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Schema\CheckConstraintBuilder;
use PHPUnit\Framework\TestCase;

class DatabaseCheckConstraintBuilderTest extends TestCase
{
    public function testSimpleWhereConstraint()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->where('status', '=', 'active', function (CheckConstraintBuilder $q) {
            $q->rule('created_at', 'IS NOT NULL');
        });

        $expected = "CHECK ((status = 'active' AND created_at IS NOT NULL))";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testMultipleWhereConstraints()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->where('type', '=', 'premium', function (CheckConstraintBuilder $q) {
            $q->rule('price', '>', 0);
        })->orWhere('type', '=', 'free', function (CheckConstraintBuilder $q) {
            $q->rule('price', '=', 0);
        });

        $expected = "CHECK ((type = 'premium' AND price > 0) OR (type = 'free' AND price = 0))";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testWhereInConstraint()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->whereIn('status', ['active', 'pending', 'inactive']);

        $expected = "CHECK (status IN ('active', 'pending', 'inactive'))";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testWhereNotInConstraint()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->whereNotIn('status', ['deleted', 'banned']);

        $expected = "CHECK (status NOT IN ('deleted', 'banned'))";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testMixedStandaloneExpressions()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->rule('age', '>=', 18)
            ->whereIn('status', ['active', 'pending'])
            ->rule('email', 'IS NOT NULL');

        $expected = "CHECK (age >= 18 AND status IN ('active', 'pending') AND email IS NOT NULL)";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testRuleWithNullOperator()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->rule('email', 'IS NOT NULL')
            ->rule('deleted_at', 'IS NULL');

        $expected = "CHECK (email IS NOT NULL AND deleted_at IS NULL)";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testRuleWithValue()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->rule('age', '>=', 18)
            ->rule('status', '!=', 'banned');

        $expected = "CHECK (age >= 18 AND status != 'banned')";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testValueQuoting()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->rule('name', '=', "O'Connor")
            ->rule('active', '=', true)
            ->rule('count', '=', 0)
            ->rule('description', '=', null);

        $expected = "CHECK (name = 'O''Connor' AND active = TRUE AND count = 0 AND description = NULL)";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testComplexNestedConstraint()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->where('type', '=', 'subscription', function (CheckConstraintBuilder $q) {
            $q->rule('start_date', 'IS NOT NULL')
                ->rule('end_date', 'IS NOT NULL')
                ->whereIn('plan', ['basic', 'premium']);
        })->orWhere('type', '=', 'one_time', function (CheckConstraintBuilder $q) {
            $q->rule('amount', '>', 0)
                ->rule('start_date', 'IS NULL');
        });

        $expected = "CHECK ((type = 'subscription' AND start_date IS NOT NULL AND end_date IS NOT NULL AND plan IN ('basic', 'premium')) OR (type = 'one_time' AND amount > 0 AND start_date IS NULL))";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testEmptyExpressions()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->where('status', '=', 'active', function (CheckConstraintBuilder $q) {
            // Empty callback
        });

        $expected = "CHECK ((status = 'active' AND ))";
        $this->assertSame($expected, $builder->toSql());
    }

    public function testNumericValues()
    {
        $builder = new CheckConstraintBuilder('test_constraint');

        $builder->rule('price', '>=', 0)
            ->rule('discount', '<=', 100.50)
            ->rule('quantity', '!=', -1);

        $expected = "CHECK (price >= 0 AND discount <= 100.5 AND quantity != -1)";
        $this->assertSame($expected, $builder->toSql());
    }
}
