@extends('errors::illustrated-layout')

@section('code', '503')
@section('title', __('Service Unavailable'))

@section('image')
<div style="background-image: url({{ asset('/svg/503.svg') }});" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
</div>
@endsection

@section('message', __($exception->getMessage() ?: __('Sorry, we are doing some maintenance. Please check back soon.')))
