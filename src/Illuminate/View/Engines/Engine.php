<?php namespace Illuminate\View\Engines; abstract class Engine { protected $lastRendered; public function getLastRendered() { return $this->lastRendered; } }
