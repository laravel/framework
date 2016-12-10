{!! strip_tags(Illuminate\Mail\Markdown::trim($header)) !!}

{!! strip_tags(Illuminate\Mail\Markdown::trim($slot)) !!}
@if (isset($subcopy))

{!! strip_tags(Illuminate\Mail\Markdown::trim($subcopy)) !!}
@endif

{!! strip_tags(Illuminate\Mail\Markdown::trim($footer)) !!}
