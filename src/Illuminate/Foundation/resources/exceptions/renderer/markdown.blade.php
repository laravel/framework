# {{ $exception->class() }} - {!! $exception->title() !!}
{!! $exception->message() !!}

## Stack trace:
@foreach($exception->frames() as $index => $frame)
{{ $index }} - {{ $frame->file() }}:{{ $frame->line() }}
@endforeach

## Request:
{{ $exception->request()->method() }} {{ Str::start($exception->request()->path(), '/') }}

## Headers

@forelse ($exception->requestHeaders() as $key => $value)
* **{{ $key }}**: {!! $value !!}
@empty
No headers data
@endforelse

## Route Context:
@forelse($exception->applicationRouteContext() as $name => $value)
{{ $name }}: {!! $value !!}
@empty
No routing data
@endforelse

@if ($routeParametersContext = $exception->applicationRouteParametersContext())
## Routing Parameters:

{!! $routeParametersContext !!}
@endif

## Database Queries:

@forelse ($exception->applicationQueries() as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
* {{ $connectionName }} - {!! $sql !!} ({{ $time }} ms)
@empty
No database queries
@endforelse
