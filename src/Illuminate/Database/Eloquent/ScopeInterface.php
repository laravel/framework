<?php namespace Illuminate\Database\Eloquent; interface ScopeInterface { public function apply(Builder $builder); public function remove(Builder $builder); }
