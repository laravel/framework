<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Content --}}
@foreach ($content as $item)
@if ($item instanceof \Illuminate\Notifications\Line)
{{ $item->content }}

@elseif ($item instanceof \Illuminate\Notifications\Action)
@php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
@endphp
<x-mail::button :url="$item->url" :color="$color">
{{ $item->text }}
</x-mail::button>
@endif
@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif

{{-- Subcopy --}}
@if (count($actions) > 0)
<x-slot:subcopy>
@if (count($actions) === 1)
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actions[0]['text'],
    ]
) <span class="break-all">[{{ str_replace(['mailto:', 'tel:'], '', $actions[0]['url']) }}]({{ $actions[0]['url'] }})</span>
@else
@lang("If you're having trouble with the buttons above, copy and paste the URLs below into your web browser:")

@foreach ($actions as $action)
**{{ $action['text'] }}:** <span class="break-all">[{{ str_replace(['mailto:', 'tel:'], '', $action['url']) }}]({{ $action['url'] }})</span>
@unless ($loop->last)

@endunless
@endforeach
@endif
</x-slot:subcopy>
@endif
</x-mail::message>
