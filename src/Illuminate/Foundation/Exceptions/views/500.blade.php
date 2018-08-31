@extends('errors::illustrated-layout')

@section('code', '500')
@section('title', 'Error')

@section('image')
<div style="background-image: url('/svg/500.svg');" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
</div>
@endsection

@section('message', 'Whoops, something went wrong on our servers.')
