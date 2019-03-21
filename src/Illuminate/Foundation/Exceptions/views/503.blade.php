@extends('errors::layout')

@section('title', 'Service Unavailable')

@section('message', __($exception->getMessage() ?: 'Be right back.'))
