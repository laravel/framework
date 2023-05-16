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
     * @var mixed|null
     */
    private $exception;

    /**
     * Create a new ScriptExecutionResult instance for a successful execution.
     *
     * @param mixed $result The result of the script execution.
     * @return self
     */
    public static function success($result)
    {
        return new self($result);
    }

    /**
     * Create a new ScriptExecutionResult instance for an error during execution.
     *
     * @param LuaScriptExecuteException|PredisError|ServerException $exception The exception thrown or error during script execution.
     * @return self
     */
    public static function error($exception)
    {
        return new self(null, $exception);
    }

    /**
     * ScriptExecutionResult constructor.
     *
     * @param mixed|null $result The result of the script execution.
     * @param \Throwable|null $exception The exception thrown during script execution.
     */
    private function __construct($result = null, $exception = null)
    {
        $this->result = $result;
        $this->exception = $exception;
    }

    /**
     * Get the exception thrown during script execution.
     *
     * @return \Throwable|null The exception instance or null if no exception occurred.
     */
    public function getException() {
        return $this->exception;
    }

    /**
     * Check if an error occurred during script execution.
     *
     * @return bool True if an error occurred, false otherwise.
     */
    public function isError() {
        return $this->exception !== null;
    }

    /**
     * Get the error type if an error occurred during script execution.
     *
     * @return string|null The error type or null if no error occurred.
     */
    public function getErrorType() {
        return $this->exception->getErrorType();
    }

    /**
     * Check if the error type is "NOSCRIPT" (no script error).
     *
     * @return bool True if the error type is "NOSCRIPT", false otherwise.
     */
    public function isNoScriptError() {
        return $this->getErrorType() === 'NOSCRIPT';
    }

    /**
     * Get the result of the script execution.
     *
     * @return mixed The result of the script execution.
     * @throws \Throwable If an error occurred during script execution, the exception is thrown.
     */
    public function getResult() {
        return !$this->isError() ? $this->result : throw $this->exception;
    }
}
