@slots(['optionalSlot', 'anotherSlot' => ['contents' => 'dummy text', 'attributes' => ['class' => 'bg-red-500']]])

<div {{ $attributes }}>
    {{ $slot }}
    <div {{ $optionalSlot->attributes }}>{{ $optionalSlot }}</div>
    <div {{ $anotherSlot->attributes }}>{{ $anotherSlot }}</div>
</div>
