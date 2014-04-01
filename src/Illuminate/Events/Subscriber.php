<?php namespace Illuminate\Events; class Subscriber { public static function subscribes() { return array(); } public static function getSubscribedEvents() { return static::subscribes(); } }
