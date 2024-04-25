<link href="//fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

<style>
    .hljs {
        background: none;
        width: 100%;
    }

    pre code.hljs {
        -ms-overflow-style: none;
        scrollbar-width: none;
        overflow-x: scroll;
        padding: 0em;
    }

    .hljs-ln .hljs-ln-numbers {
        padding: 5px;
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
            background-color: rgba(242, 95, 95, 0.4);
        }
    </style>
@endforeach
