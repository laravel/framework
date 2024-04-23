@if (isset($globalParam))
    <p>Global Param: {{ $globalParam }}</p>
@else
    <p>Global Param is not set</p>
@endif
@if (isset($param1))
    <p>Param 1: {{ $param1 }}</p>
@else
    <p>Param 1 is not set</p>
@endif
@if (isset($param2))
    <p>Param 2: {{ $param2 }}</p>
@else
    <p>Param 2 is not set</p>
@endif
