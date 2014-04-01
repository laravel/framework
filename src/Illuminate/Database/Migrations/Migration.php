<?php namespace Illuminate\Database\Migrations; abstract class Migration { protected $connection; public function getConnection() { return $this->connection; } }
