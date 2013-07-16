<?php namespace Illuminate\Database;

class QueryException extends \PDOException {

    protected $bindings = array();

    protected $sql = '';

    public function __construct($sql, array $bindings, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        parent::__construct($message, $code, $previous);
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function getSqlBindings()
    {
        $bindings = strtr(var_export($this->bindings, true), array("\n" => ''));
        return "(SQL: $this->sql) (Bindings: $bindings)";
    }
}