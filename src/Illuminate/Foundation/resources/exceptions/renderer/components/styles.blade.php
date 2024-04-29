<link href="//fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"></script>
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/material.css"></script>

<style type="text/css">
    html {
        tab-size: 4;
    }

    table.hljs-ln {
        color: inherit;
        font-size: inherit;
        border-spacing: 2px;
    }

    .hljs {
        background: none;
        width: 100%;
    }

    pre code.hljs {
        padding: 0em;
        padding-top: 0.5em;
    }

    .hljs-ln-line {
        white-space-collapse: preserve;
        text-wrap: nowrap;
    }

    .trace {
        -webkit-mask-image: linear-gradient(180deg,#000 calc(100% - 4rem),transparent);
    }

    .scrollbar-hidden {
        -ms-overflow-style: none;
        scrollbar-width: none;
        overflow-x: scroll;
    }

    .scrollbar-hidden::-webkit-scrollbar {
        -webkit-appearance: none;
        width: 0;
        height: 0;
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
    <style type="text/css">
        #frame-{{ $loop->index }} .hljs-ln-line[data-line-number='{{ $frame->line() }}'] {
            background-color: rgba(242, 95, 95, 0.4);
        }
    </style>
@endforeach
