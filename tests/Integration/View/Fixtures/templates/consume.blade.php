@isset($color)

<x-menu :color="$color">
<x-menu-item color="blue">C</x-menu-item>
<x-menu-item>D</x-menu-item>
</x-menu>

@else

<x-menu>
<x-menu-item color="blue">C</x-menu-item>
<x-menu-item>D</x-menu-item>
</x-menu>

@endisset
