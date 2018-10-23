@extends('errors/layout')

@section('title', 'Error 404 - Page not found')

@section('content')
    <aside class="item aside-item text-yellow">404</aside>
    <header class="item header-item">
        <h2><i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i> Opps! Page not found</h2>
        <p class="description">We could not find the page you were looking for. Meanwhile, you may <a href="{{ URL::route('dashboard') }}">return to dashboard</a>.</p>
    </header>
@endsection
