@props([
    'input'
])

<div>
    <input type="text" {{ $input->attributes->class('input') }} />
</div>
