@if('defaults' === $mode)

<x-menu>
<x-menu-item>Default item 1</x-menu-item>
<x-menu-item>Default item 2</x-menu-item>
</x-menu>

@elseif('override')

<x-menu color="pink">
<x-menu-item color="yellow">Default item 1</x-menu-item>
<x-menu-item>Default item 2</x-menu-item>
</x-menu>

@else

<x-menu color="pink">
<x-menu-item>Default item 1</x-menu-item>
<x-menu-item>Default item 2</x-menu-item>
</x-menu>

@endif
