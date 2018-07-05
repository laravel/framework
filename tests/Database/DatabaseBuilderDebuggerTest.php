<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Grammars\MySqlGrammar;

class DatabaseBuilderDebuggerTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetRawSql()
    {
        $builder = $this->getMysqlBuilder();

        return $builder->select('*')
            ->from('users')
            ->where('users.status', 'active')
            ->where('users.age', '>', 18)
            ->whereRaw('users.locale = :locale', ['locale' => 'en'])
            ->whereIn('users.group', [1, 2, 3, 'string'])
            ->whereRaw('users.something_else = :binding', ['unnamed_binding_value"with\'quotes']);

        $this->assertSame(
            "select * from `users` where `users`.`status` = 'active' and `users`.`age` > 18 and users.locale = 'en' and `users`.`group` in (1, 2, 3, 'string') and users.something_else = 'unnamed_binding_value\"with\'quotes\'",
            $builder->debugger()->getRawSql()
        );
    }

    /**
     * @return Builder
     * @throws \ReflectionException
     */
    private function getMysqlBuilder(): Builder
    {
        /** @var ConnectionInterface $connection */
        /** @var Processor $processor */
        $connection = $this->createMock(ConnectionInterface::class);
        $processor = $this->createMock(Processor::class);

        return new Builder(
            $connection,
            new MySqlGrammar(),
            $processor
        );
    }
}
