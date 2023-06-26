@if($noOptionals)
<x-optional-slot>
    Slot content
</x-optional-slot>
@elseif($optionalAsAttribute)
<x-optional-slot optionalSlot="Optional content" anotherSlot="Another slot content">
    Slot content
</x-optional-slot>
@else
<x-optional-slot>
    Slot content
    <x-slot:optionalSlot>
        Optional content
    </x-slot:optionalSlot>
    <x-slot:anotherSlot>
        Another slot content
    </x-slot:anotherSlot>
</x-optional-slot>
@endif
