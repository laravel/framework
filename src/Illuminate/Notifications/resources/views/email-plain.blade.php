@if(! empty($greeting)) {
    {{ $greeting }}
@else
    {{ $level == 'error' ? 'Whoops!' : 'Hello!' }}
@endif


@if(! empty($introLines)) {
    {{ implode("\n", $introLines) }}
@endif


@if(isset($actionText)) {
    {{ $actionText }}: {{ $actionUrl }}
@endif


@if(! empty($outroLines)) {
    {{ implode("\n", $outroLines) }}
@endif


Regards,
{{ config('app.name') }}
