<?php

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;

require dirname(__DIR__).'/vendor/autoload.php';

$items = array_fill(0, 100, [
    'sku' => 'ABC-123',
    'quantity' => 2,
    'price' => 19.99,
    'status' => 'active',
    'email' => 'customer@example.com',
]);

$rules = [
    'items.*.sku' => ['required', 'string', 'max:32'],
    'items.*.quantity' => ['required', 'integer', 'min:1'],
    'items.*.price' => ['required', 'numeric'],
    'items.*.status' => ['required', 'in:active,inactive'],
    'items.*.email' => ['nullable', 'email:filter'],
];

$translator = new Translator(new ArrayLoader, 'en');

$validator = new Validator($translator, ['items' => $items], $rules);

$validator->passes();
