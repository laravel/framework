@props(['name'])

<div {{ $attributes->merge(['class' => 'mt-4', 'data-controller' => $attributes->prepends('inside-controller')]) }}>
    Hello {{ $name }}
</div>
