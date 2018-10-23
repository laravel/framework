@extends('layouts/auth_template')

@section('dynamicStyles')
    <link rel="stylesheet" href="{{ URL::assetUrl('/css/custome.css') }}" media="screen" title="no title" charset="utf-8">
@endsection

@section('content')
    <div class="login-box">
        @if(session('returnMessage'))
            <?php $messageData = ['error' => ['cssClass' => 'error', 'iconType' => 'ban'], 'success' => ['cssClass' => 'success', 'iconType' => 'check'], 'warning' => ['cssClass' => 'warning', 'iconType' => 'warning'], 'info' => ['cssClass' => 'info', 'iconType' => 'info']] ?>
            <div class="alert alert-{{$messageData[session('msgType')]['cssClass']}}" id="returnMessageAlert">
                <strong id="alert-title"><i class="icon fa fa-{{$messageData[session('msgType')]['iconType']}}"></i> {{session('alertTitle')}} : </strong>
                <span id="alert-data">{{session('alertMessage')}}</span>
            </div>
        @endif
        <div class="login-box-body">
            @include('/layouts/partials/LogoHeader')
            <p class="login-box-msg">Enter the username that you used to Log In</p>

            @if (session()->has('error'))
                <label class="error">{{ session('error') }}</label>
            @endif

            @if (session()->has('msg'))
                <label class="success">{{ session('msg') }}</label>
            @endif

            <form id="ForgotPasswordForm" name="ForgotPasswordForm" action="{{ URl::asset('/login')}}" method="post">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="Username">Username</label>
                    <input type="text" class="form-control" placeholder="Email or Mobile No" name="Username" id="Username"/>
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <button type="submit" class="btn btn-primary btn-flat" id="continue">Continue</button>
                        <a href="{{ route('login') }}" class="fl-rt" style="margin-top:0.4em">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="alert hidden" id="ForgotPasswordFormAlert">
            <strong id="alert-title"></strong>
            <span id="alert-data"></span>
        </div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/validation/jquery.validate.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/validation/additional-methods.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/forgotPassword.js') }}"></script>
@endsection
