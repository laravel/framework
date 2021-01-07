@if ($withInjectedValue)
    <x-appendable-panel class="bg-gray-100" :name="$name" data-controller="outside-controller" foo="bar">
        Panel contents
    </x-appendable-panel>
@else
    <x-appendable-panel class="bg-gray-100" :name="$name" foo="bar">
        Panel contents
    </x-appendable-panel>
@endif
