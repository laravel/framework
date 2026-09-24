<?php

namespace Illuminate\Tests\Database\Concerns;

use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

trait RestoresConnectionResolver
{
    private ?ConnectionResolverInterface $originalConnectionResolver = null;

    #[Before]
    protected function rememberConnectionResolver(): void
    {
        $this->originalConnectionResolver = Model::getConnectionResolver();
    }

    #[After]
    protected function restoreConnectionResolver(): void
    {
        $this->originalConnectionResolver
            ? Model::setConnectionResolver($this->originalConnectionResolver)
            : Model::unsetConnectionResolver();
    }

    protected function useInMemoryConnection(): SQLiteConnection
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'));

        $resolver = new ConnectionResolver(['default' => $connection]);
        $resolver->setDefaultConnection('default');
        Model::setConnectionResolver($resolver);

        return $connection;
    }
}
