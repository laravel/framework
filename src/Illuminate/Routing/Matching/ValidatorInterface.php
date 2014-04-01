<?php namespace Illuminate\Routing\Matching; use Illuminate\Http\Request; use Illuminate\Routing\Route; interface ValidatorInterface { public function matches(Route $route, Request $request); }
