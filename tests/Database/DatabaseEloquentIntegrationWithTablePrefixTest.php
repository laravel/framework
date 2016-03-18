<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentIntegrationWithTablePrefixTest extends DatabaseEloquentIntegrationTest
{
    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $resolver = new DatabaseIntegrationTestConnectionResolver;
        $resolver->connection()->setTablePrefix('prefix_');
        Eloquent::setConnectionResolver($resolver);

        Eloquent::setEventDispatcher(
            new Illuminate\Events\Dispatcher
        );
    }

    public function testBasicModelHydration()
    {
        EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['email' => 'abigailotwell@gmail.com']);

        $models = EloquentTestUser::hydrateRaw('SELECT * FROM prefix_users WHERE email = ?', ['abigailotwell@gmail.com'], 'foo_connection');

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $models);
        $this->assertInstanceOf('EloquentTestUser', $models[0]);
        $this->assertEquals('abigailotwell@gmail.com', $models[0]->email);
        $this->assertEquals('foo_connection', $models[0]->getConnectionName());
        $this->assertEquals(1, $models->count());
    }
}
