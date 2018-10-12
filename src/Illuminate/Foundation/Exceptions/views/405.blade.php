@extends('errors::illustrated-layout')

@section('code', '405')
@section('title', __('Method Not Allowed'))

@section('image')
<div style="background-image: url('/svg/405.svg');" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
</div>
@endsection

@section('message', __('Sorry, the request method is not supported for the requested resource.'))