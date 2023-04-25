@extends('errors::minimal')

@php
    $statusCode = $exception->getStatusCode();
    $statusText = __(\Symfony\Component\HttpFoundation\Response::$statusTexts[$statusCode]);
    $statusMessage = !empty($exception->getMessage()) ? ': ' . __($exception->getMessage()) : '';
@endphp

@section('title', "$statusCode: $statusText")
@section('code', $statusCode)
@section('message', $statusText . $statusMessage )
