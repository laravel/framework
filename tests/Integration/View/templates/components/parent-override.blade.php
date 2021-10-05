@props(['color' => 'red'])

<div>
    <span>I like the color {{ $color }}!</span>
    <x-child color="pink" />
</div>
