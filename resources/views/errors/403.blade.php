@extends('errors/layout')

@section('title', 'Error 403 - Access forbidden')

@section('content')
    <aside class="item aside-item text-red">403</aside>
    <header class="item header-item">
        <h2><i class="fa fa-exclamation-triangle text-red" aria-hidden="true"></i> Opps! Permission denied</h2>
        <p class="description">Please contact site administrator to have access to this page. Meanwhile, you may <a href="{{ URL::route('dashboard') }}">return to dashboard</a>.</p>
    </header>
@endsection
