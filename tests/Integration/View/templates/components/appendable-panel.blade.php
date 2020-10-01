@props(['name'])

<div {{ $attributes->merge(['class' => 'mt-4', 'data-controller' => $attributes->appends('inside-controller')]) }}>
    Hello {{ $name }}
</div>
