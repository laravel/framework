@extends('errors::illustrated-layout')

@section('code', '403')
@section('title', 'Unauthorized')

@section('image')
<div style="background-image: url('/svg/403.svg');" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
</div>
@endsection

@section('message', 'Sorry, you may not access this page.')
