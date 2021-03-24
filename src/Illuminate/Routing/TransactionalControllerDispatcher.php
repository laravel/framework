<?php

namespace Illuminate\Routing;

use Throwable;
use Illuminate\Contracts\Routing\TransactionManager;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;

class TransactionalControllerDispatcher implements ControllerDispatcherContract
{
    /**
     * @var ControllerDispatcherContract
     */
    private $controllerDispatcher;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    public function __construct(ControllerDispatcherContract $controllerDispatcher, TransactionManager $transactionManager)
    {
        $this->controllerDispatcher = $controllerDispatcher;
        $this->transactionManager = $transactionManager;
    }

    /**
     * Dispatch a request to a given controller and method in a transactional manner
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {
        $this->transactionManager->beginTransaction();
        try {
            $response = $this->controllerDispatcher->dispatch($route, $controller, $method);
            $this->transactionManager->commit();
            return $response;
        } catch (Throwable $throwable) {
            $this->transactionManager->rollback();
            throw $throwable;
        }
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method)
    {
        return $this->controllerDispatcher->getMiddleware($controller, $method);
    }
}
