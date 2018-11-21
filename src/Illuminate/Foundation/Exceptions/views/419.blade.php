@extends('errors::illustrated-layout')

@section('code', '419')
@section('title', __('Page Expired'))

@section('image')
<div style="background-image: url({{ asset('/svg/403.svg') }});" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
</div>
@endsection

@section('message', __('Sorry, your session has expired. Please refresh and try again.'))
