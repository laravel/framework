<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentIntegrationWithPdoErrModeExceptionTest extends DatabaseEloquentIntegrationTest
{
    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $resolver = new DatabaseIntegrationTestConnectionResolver;
        $resolver->connection()->getPdo()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Eloquent::setConnectionResolver($resolver);

        Eloquent::setEventDispatcher(
            new Illuminate\Events\Dispatcher
        );
    }

    public function testCountForPaginationWithGrouping()
    {
        $this->setExpectedException('Illuminate\Database\QueryException');

        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);
        EloquentTestUser::create(['id' => 3, 'email' => 'foo@gmail.com']);
        EloquentTestUser::create(['id' => 4, 'email' => 'foo@gmail.com']);

        $query = EloquentTestUser::groupBy('email')->getQuery();

        $this->assertEquals(3, $query->getCountForPagination());
    }

    public function testCountForPaginationWithPassingColumns()
    {
        $columns = ['id', 'email'];

        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);
        EloquentTestUser::create(['id' => 3, 'email' => 'foo@gmail.com']);

        $query = EloquentTestUser::oldest('id')->getQuery();

        $total = $query->getCountForPagination($columns);

        $this->assertEquals(3, $total);
    }

    public function testEmptyMorphToRelationship()
    {
        $this->setExpectedException('Illuminate\Database\QueryException');

        $photo = EloquentTestPhoto::create(['name' => 'Avatar 1']);

        $this->assertNull($photo->imageable);
    }
}
