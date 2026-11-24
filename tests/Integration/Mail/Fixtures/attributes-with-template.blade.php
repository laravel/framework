@component('mail::message')
<img id="test-image" src="https://example.com/image.png" alt="{{ $value }}" title='{{ $value }}'>
<span id="test-helper" title="{!! e($value, false) !!}"></span>

@component('mail::button', ['url' => $value])
Continue
@endcomponent
@endcomponent
