@if('defaults' === $mode)

<x-menu>
<x-menu-item color="blue">Default item 1</x-menu-item>
<x-menu-item>Default item 2</x-menu-item>
</x-menu>

@else

<x-menu color="pink">
<x-menu-item color="blue">Default item 1</x-menu-item>
<x-menu-item>Default item 2</x-menu-item>
</x-menu>

@endif
