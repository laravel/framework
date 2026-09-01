@props(['disabled' => false])

@php
if ($disabled) {
    $class = 'disabled-class';
} else {
    $class = 'not-disabled-class';
}
@endphp

<input {{ $attributes->merge(['class' => $class]) }} {{ $disabled ? 'disabled' : '' }} />