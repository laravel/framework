@extends('errors::layout')

@section('title', 'Service Unavailable')

@section('message', __($exception->getMessage() ?: 'Service Unavailable'))
