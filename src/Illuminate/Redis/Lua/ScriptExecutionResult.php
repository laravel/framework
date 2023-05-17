<?php

namespace Illuminate\Redis\Lua;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Predis\Response\Error as PredisError;
use Predis\Response\ServerException;

class ScriptExecutionResult
{
    /**
     * @var mixed|null
     */
    private $result;

    /**
     * @var \Illuminate\Contracts\Redis\LuaScriptExecuteException|null
     */
    private $exception;

    /**
     * Create a new ScriptExecutionResult instance for a successful execution.
     *
     * @param  mixed  $result  The result of the script execution.
     * @return self
     */
    public static function success($result)
    {
        return new self($result);
    }

    /**
     * Create a new ScriptExecutionResult instance for an error during execution.
     *
     * @param  \Illuminate\Contracts\Redis\LuaScriptExecuteException|\Predis\Response\Error|\Predis\Response\ServerException  $exception  The exception thrown or error during script execution.
     * @return self
     */
    public static function error($exception)
    {
        if ($exception instanceof PredisError || $exception instanceof ServerException) {
            $exception = new LuaScriptExecuteException($exception->getMessage());
        }

        return new self(null, $exception);
    }

    /**
     * ScriptExecutionResult constructor.
     *
     * @param  mixed|null  $result  The result of the script execution.
     * @param  \Illuminate\Contracts\Redis\LuaScriptExecuteException|null  $exception  The exception thrown during script execution.
     */
    private function __construct($result = null, $exception = null)
    {
        $this->result = $result;
        $this->exception = $exception;
    }

    /**
     * Get the exception thrown during script execution.
     *
     * @return \Illuminate\Contracts\Redis\LuaScriptExecuteException|null The exception instance or null if no exception occurred.
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Check if an error occurred during script execution.
     *
     * @return bool True if an error occurred, false otherwise.
     */
    public function isError()
    {
        return $this->exception !== null;
    }

    /**
     * Get the error type if an error occurred during script execution.
     *
     * @return string|null The error type or null if no error occurred.
     */
    public function getErrorType()
    {
        return $this->isError() ? $this->exception->getErrorType() : null;
    }

    /**
     * Check if the error type is "NOSCRIPT" (no script error).
     *
     * @return bool True if the error type is "NOSCRIPT", false otherwise.
     */
    public function isNoScriptError()
    {
        return $this->getErrorType() === 'NOSCRIPT';
    }

    /**
     * Get the result of the script execution.
     *
     * @return mixed The result of the script execution.
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If an error occurred during script execution, the exception is thrown.
     */
    public function getResult()
    {
        return ! $this->isError() ? $this->result : throw $this->exception;
    }

    /**
     * Throws an exception if an error occurred during script execution.
     *
     * @return void
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If an error occurred during script execution, the exception is thrown.
     */
    public function throwIfError()
    {
        if ($this->isError()) {
            throw $this->exception;
        }
    }
}
