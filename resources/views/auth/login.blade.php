@extends ('layouts/auth_template')

@section ('content')
    <div class="login-box">
        @if (session()->has('loginMessage'))
            <div class="alert alert-{{ session('loginMessage.type') }}">
                <p class="body">{{ session('loginMessage.body') }}</p>
            </div>
        @endif
        <div class="login-box-body">
            @include('/layouts/partials/LogoHeader')
            <form id="LoginForm" name="LoginForm" action="{{ route('login') }}" method="POST">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="Username">Username</label>
                    <input type="text" class="form-control" placeholder="Email or Mobile" name="Username" id="Username" value="{{ old('Username') }}"/>
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="Password">Password</label>
                    <input type="password" class="form-control" placeholder="Password" name="Password" id="Password"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat" id="LoginFormSubmit">Log In</button>
                    </div>
                </div>
            </form>
            <div>
                <a href="{{ route('forgotpassword') }}">Forgot password?</a>
                <a href="{{ route('signup') }}"  class="pull-right text-center">Sign Up</a>
            </div>
            <div class="overlay hidden" id="LoginFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Authenticating...</div>
            </div>
            <div class="mr-tp-10">
                <span class="pd-0">
                    <i class="fa fa-phone-square" aria-hidden="true"></i> {{ $domain->mobile }}
                </span>
                <span class="pull-right domain-email">
                    <i class="fa fa-envelope-square" aria-hidden="true"></i>
                    <a href="mailto:{{ $domain->email }}" target="_top">{{ $domain->email }}</a>
                </span>       
            </div>
        </div>
        <div id="NotificationArea"></div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/validation/jquery.validate.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/validation/additional-methods.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ asset('js/auth/login.js') }}"></script>
@endsection

@section("dynamicStyles")
    <link rel="stylesheet" type="text/css" href="{{ asset('css/auth/login.css') }}"/>
@endsection
