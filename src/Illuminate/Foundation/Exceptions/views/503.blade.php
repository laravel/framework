@extends('errors::layout')

@section('title', 'Service Unavailable')

@if ($message = $exception->getMessage())
    @section('message', $message)
@else
    @section('message', 'Be right back.')
@endif
