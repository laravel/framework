<?php

namespace Illuminate\Database\Concerns;

trait ConfiguresCustomInitCommands
{
    /**
     * Configure custom database init commands.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureCustomInitCommands($connection, array $config)
    {
        if (isset($config['init']) && is_array($config['init'])) {
            foreach ($config['init'] as $command) {
                $connection->prepare($command)->execute();
            }
        }
    }
}
