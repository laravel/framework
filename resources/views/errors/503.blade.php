@extends('errors/layout')

@section('title', 'Error 503 - Service unavailable')

@section('content')
    <aside class="item aside-item text-red">503</aside>
    <header class="item header-item">
        <h2><i class="fa fa-exclamation-triangle text-red" aria-hidden="true"></i> Opps! Something went wrong.</h2>
        <p class="description">We will work on fixing that right away. Meanwhile, you may <a href="{{ URL::route('dashboard') }}">return to dashboard</a>.</p>
    </header>
@endsection
