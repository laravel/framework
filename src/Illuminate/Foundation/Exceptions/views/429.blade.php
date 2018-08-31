@extends('errors::illustrated-layout')

@section('code', '429')
@section('title', 'Too Many Requests')

@section('image')
<div style="background-image: url('/svg/403.svg');" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
</div>
@endsection

@section('message', 'Sorry, you are making too many requests to our servers.')
