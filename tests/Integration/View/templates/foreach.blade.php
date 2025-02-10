@foreach(['foo' => ['title' => 'First', 'words' => ['foo', 'bar']], 'bar' => ['title' => 'Second', 'words' => ['bar', 'baz']]])
    {{ $it['title'] }}
    @foreach($it['words'])
        {{ $it }}
    @endforeach
    {{ $it['title'] }}
@endforeach
