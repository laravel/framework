<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\InteropQueue;
use Interop\Queue\PsrConnectionFactory;

class InteropConnector implements ConnectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $config)
    {
        $config = array_replace([
            'connection_factory_class' => null,
            'dsn' => null,
            'queue' => 'default',
            'time_to_run' => 0,
        ], $config);

        if (empty($config['connection_factory_class'])) {
            throw new \LogicException('The "connection_factory_class" option is required');
        }

        $factoryClass = $config['connection_factory_class'];
        if (false == class_exists($factoryClass)) {
            throw new \LogicException(sprintf('The "connection_factory_class" option "%s" is not a class', $factoryClass));
        }

        $rc = new \ReflectionClass($factoryClass);
        if (false == $rc->implementsInterface(PsrConnectionFactory::class)) {
            throw new \LogicException(sprintf('The "connection_factory_class" option must contain a class that implements "%s" but it is not', PsrConnectionFactory::class));
        }

        /** @var PsrConnectionFactory $factory */
        $factory = new $factoryClass($config['dsn'] ? $config['dsn'] : $config);

        return new InteropQueue($factory->createContext(), $config['queue'], $config['time_to_run']);
    }
}
