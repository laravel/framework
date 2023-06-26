@slots(['optional_slot', 'another_slot' => ['contents' => 'dummy text', 'attributes' => ['class' => 'bg-red-500']]])

<div {{ $attributes }}>
    {{ $slot }}
    <div {{ $optional_slot->attributes }}>{{ $optional_slot }}</div>
    <div {{ $another_slot->attributes }}>{{ $another_slot }}</div>
</div>
