@extends('layouts/auth_template')
@section('content')
<div class="login-box">
    @if (session('returnMessage'))
    <?php $messageData = ['error' => ['cssClass' => 'danger', 'iconType' => 'ban'], 'success' => ['cssClass' => 'success', 'iconType' => 'check'], 'warning' => ['cssClass' => 'warning', 'iconType' => 'warning'], 'info' => ['cssClass' => 'info', 'iconType' => 'info']] ?>
    <div class="alert alert-{{$messageData[session('msgType')]['cssClass']}}">
        <strong id="alert-title"><i class="icon fa fa-{{$messageData[session('msgType')]['iconType']}}"></i>{{session('alertTitle')}} : </strong>
        <span id="alert-data">{{session('alertMessage')}}</span>
    </div>
    @endif
    <div class="login-box-body">
        @include('/layouts/partials/LogoHeader')
        @if (session()->has('error'))
        <label class="error">{{ session('error') }}</label>
        @endif
        @if (session()->has('msg'))
        <label class="success">{{ session('msg') }}</label>
        @endif
        <form id="LoginForm" name="LoginForm" action="{{ URl::asset('/login')}}" method="POST">
            {{ csrf_field() }}
            <div class="form-group has-feedback">
                <label for="UserName">Username</label>
                <input type="text" class="form-control" placeholder="Email or Mobile No" name="UserName" id="UserName" value="{{ old('UserName') }}">
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <label for="Password">Password</label>
                <input type="password" class="form-control" placeholder="Password" name="Password" id="Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Log In</button>
                </div>
            </div>
        </form>
        <div class="">
            <a href="{{ route('forgotpassword') }}">Forgot password?</a>
            <a href="{{ route('registration') }}"  class="pull-right text-center">Sign Up</a>
        </div>
        <div class="mr-tp-10">
            <span class="pd-0"><i class="fa fa-phone-square" aria-hidden="true"></i>&nbsp; {{ $CurrentDomainSettings->ContactNumber }}</span>
            <span class="pull-right"><i class="fa fa-envelope-square" aria-hidden="true"></i>&nbsp; <a href="mailto:{{ $CurrentDomainSettings->Email }}" target="_top">{{ $CurrentDomainSettings->Email }}</a></span>       
        </div>
        
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/validation/jquery.validate.min.js') }}"></script>
<script src="{{ URL::assetUrl('/validation/additional-methods.min.js') }}"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}"></script>
<script src="{{ URL::assetUrl('/js/login.js') }}"></script>
@endsection
