<?php namespace Illuminate\Foundation\Testing;

trait InMemoryDatabase
{
    /**
     * @before
     */
    public function enableInMemoryDatabase()
    {
        $connection = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ];

        config()->set('database.connections.testing', $connection);
        config()->set('database.default', 'testing');
    }
}
