@extends ('layouts/auth_template')

@section ('dynamicStyles')
    <link rel="stylesheet" href="{{ asset('css/custome.css') }}"/>
@endsection

@section('content')
    <div class="login-box">
        @if (session()->has('OTPMessage'))
            <div class="alert alert-{{ session('OTPMessage.type') }}">
                <p class="body">{{ session('OTPMessage.body') }}</p>
            </div>
        @endif
        <div class="login-box-body">
            @include('/layouts/partials/LogoHeader')
            <form id="OTPForm" name="OTPForm" action="{{ route('account.otp') }}" method="POST">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="Mobile">Mobile</label>
                    <input type="text" class="form-control" readonly value="{{ $mobile }}" id="Mobile"/>
                    <span class="glyphicon glyphicon-phone form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="OTP">One Time Password</label>
                    <input type="text" class="form-control" placeholder="Enter the OTP to confirm" name="OTP" id="OTP" autofocus="autofocus"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat" id="OTPFormSubmit">Continue</button>
                    </div>
                    <div class="col-xs-12 col-sm-6 fl-rt text-right pd-tp-6">
                        <a href="{{ route('account.otp.resend') }}" id="resendOTP" class="text-resend-otp">Resend OTP</a>
                    </div>
                </div>
            </form>
            <div class="overlay hidden" id="OTPFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Verifying...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('js/common.js') }}" charset="utf-8"></script>
    <script src="{{ asset('js/otp/account.js') }}" charset="utf-8"></script>
@endsection
