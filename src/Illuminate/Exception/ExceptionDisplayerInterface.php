<?php namespace Illuminate\Exception; use Exception; interface ExceptionDisplayerInterface { public function display(Exception $exception); }
