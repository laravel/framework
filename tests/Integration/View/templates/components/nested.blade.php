@if (isset($nested) == true && $nested == true)
    <small>{{ $count }}</small>
@else
    <div>
        {{ $count }}
        @foreach (range(1, 3) as $c)
            <x-nested :count="$c" :nested="true" />
        @endforeach
    </div>
@endif
