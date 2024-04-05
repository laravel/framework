<link href="//fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" />

<style>
    .hljs {
        background: none;
        width: 100%;
    }

    .hljs-ln td {
        padding: 5px;
        color: #2d3748;
        border-right-color: transparent;
        margin-right: 5px;
    }

    .hljs-ln-n {
        width: 50px;
    }

    .hljs-ln-numbers {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;

        text-align: center;
        color: #ccc;
        border-right: 1px solid #ccc;
        vertical-align: top;
        padding-right: 5px;
    }

    .hljs-ln-code {
        width: 100%;
        padding-left: 10px;
        padding-right: 10px;
    }

    .hljs-ln-code:hover {
        background-color: rgba(239, 68, 68, 0.2);
    }
</style>

@foreach ($exception->frames() as $frame)
    <style>
        #frame-{{ $loop->index }} .hljs-ln-line[data-line-number='{{ $frame->line() }}'] {
            background-color: rgba(239, 68, 68, 0.4);
        }

        #frame-{{ $loop->index }} .hljs-ln-numbers[data-line-number='{{ $frame->line() }}'] {
            color: rgba(239, 68, 68, 0.4);
        }

        #frame-{{ $loop->index }} .hljs-ln-code[data-line-number='{{ $frame->line() }}'] {
            color: rgba(239, 68, 68, 0.4);
        }
    </style>
@endforeach
