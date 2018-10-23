@extends ('layouts/auth_template')

@section ('content')
    <div class="login-box">
        @if (session()->has('passwordMessage'))
            <div class="alert alert-{{ session('passwordMessage.type') }}">
                <p class="body">{{ session('passwordMessage.body') }}</p>
            </div>
        @endif
        <div class="login-box-body">
            @include('/layouts/partials/LogoHeader')
            <form id="ForgotPasswordForm" name="ForgotPasswordForm" action="{{ route('forgotpassword') }}" method="post">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="Username">Username</label>
                    <input type="text" class="form-control" placeholder="Email or Mobile" name="Username" id="Username"/>
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <input type="submit" value="Send" class="btn btn-primary btn-flat pd-rt-25 pd-lt-25" id="ForgotPasswordFormSubmit"/>
                        <a href="{{ route('login') }}" class="fl-rt mr-tp-8">Cancel</a>
                    </div>
                </div>
            </form>
            <div class="overlay hidden" id="ForgotPasswordFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Sending...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ asset('validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('js/common.js') }}"></script>
    <script src="{{ asset('js/auth/forgotpassword.js') }}"></script>
@endsection
