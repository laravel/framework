@extends('layouts/auth_template')

@section('dynamicStyles')
    <link rel="stylesheet" href="{{ URL::assetUrl('/css/custome.css') }}" media="screen" title="no title" charset="utf-8">
@endsection

@section('content')
    <div class="login-box">
        @if(session()->has('msgType') || isset($returnMessage))
            <?php
            $msgType = session()->has('msgType') ? session()->get('msgType') : $msgType;
            $alertTitle = session()->has('alertTitle') ? session()->get('alertTitle') : $alertTitle;
            $alertMessage = session()->has('alertMessage') ? session()->get('alertMessage') : $alertMessage;
            $messageData = ['error' => ['cssClass' => 'error', 'iconType' => 'ban'], 'success' => ['cssClass' => 'success', 'iconType' => 'check'], 'warning' => ['cssClass' => 'warning', 'iconType' => 'warning'], 'info' => ['cssClass' => 'info', 'iconType' => 'info']];
            ?>
            <div class="alert alert-{{$messageData[$msgType]['cssClass']}}">
                <strong id="alert-title"><i class="icon fa fa-{{$messageData[$msgType]['iconType']}}"></i> {{$alertTitle}} : </strong>
                <span id="alert-data">{{$alertMessage}}</span>
            </div>
        @endif
        <div class="login-box-body">
            @include('/layouts/partials/LogoHeader')
            <p class="login-box-msg">Enter the OTP that you got on your mobile</p>
            @if (session()->has('error'))
                <label class="error">{{ session('error') }}</label>
            @endif
            @if (session()->has('msg'))
                <label class="success">{{ session('msg') }}</label>
            @endif
            <form id="OTPForm" name="OTPForm" action="{{ URl::route($postURL)}}" method="post">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="PhoneNumber">Mobile number</label>
                    <input type="text" class="form-control" readonly value="{{$mobileNo}}" id="PhoneNumber"/>
                    <span class="glyphicon glyphicon-phone form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="OTP">One Time Password</label>
                    <input type="text" class="form-control" placeholder="Enter the OTP to confirm" name="OTP" id="OTP"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat" id="continue">Continue</button>
                    </div>
                    <div class="col-xs-12 col-sm-6 fl-rt">
                        <div id="resendOTP" class="text-resend-otp text-hover text-right" style="margin-top:0.5em">Resend OTP</div>
                    </div>
                </div>
            </form>
        </div>
        <div class="alert hidden" id="OTPFormAlert">
            <strong id="alert-title"></strong>
            <span id="alert-data"></span>
        </div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ URl::asset('/validation/jquery.validate.min.js')}}" charset="utf-8"></script>
    <script src="{{ URl::asset('/js/common.js')}}" charset="utf-8"></script>
    <script src="{{ URl::asset('/js/otp.js')}}" charset="utf-8"></script>
@endsection
