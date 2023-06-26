@if($noOptionals)
<x-optional-slot>
    Slot content
</x-optional-slot>
@elseif($optionalAsAttribute)
<x-optional-slot optional_slot="Optional content" another_slot="Another slot content">
    Slot content
</x-optional-slot>
@else
<x-optional-slot>
    Slot content
    <x-slot:optional_slot>
        Optional content
    </x-slot:optional_slot>
    <x-slot:another_slot>
        Another slot content
    </x-slot:another_slot>
</x-optional-slot>
@endif
