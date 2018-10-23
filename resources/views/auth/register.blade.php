@extends ('layouts/auth_template')

@section ('content')
<style>
    .form-group.has-error label {
    color: #ef1a00 !important;
}
</style>
    <div class="register-box">
        @if (session()->has('signupMessage'))
            <div class="alert alert-{{ session('signupMessage.type') }}">
                <p class="body">{{ session('signupMessage.body') }}</p>
            </div>
        @endif
        <div class="register-box-body">
            @include('/layouts/partials/LogoHeader')
            <form name='SignupForm' id='SignupForm' action="{{ route('signup') }}" method="POST">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="FirstName">First name<span class="text-danger"> *</span></label>
                    <input name="FirstName" id="FirstName" type="text" class="form-control" placeholder="Ex: John"/>
                    <span class="fa fa-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="LastName">Last name<span class="text-danger"> *</span></label>
                    <input name="LastName" id="LastName" type="text" class="form-control" placeholder="Ex: Doe"/>
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="Mobile">Mobile<span class="text-danger"> *</span></label>
                    <input name="Mobile" id="Mobile" type="text" class="form-control" placeholder="Ex: 9999999999" data-entity-existence-url="{{ route('check.mobile') }}"/>
                    <span class="fa fa-phone form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="Email">Email<span class="text-danger"> *</span></label>
                    <input name="Email" id="Email" type="email" class="form-control" placeholder="Ex: user@example.com" data-entity-existence-url="{{ route('check.email') }}"/>
                    <span class="fa fa-envelope form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="Password">Password<span class="text-danger"> *</span></label>
                    <input name="Password" id="Password" type="password" class="form-control" placeholder="Password"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="ConfirmPassword">Confirm Password<span class="text-danger"> *</span></label>
                    <input name="ConfirmPassword" id="ConfirmPassword" type="password" class="form-control" placeholder="Confirm Password"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <input type="submit" class="btn btn-primary btn-flat" id="SignupFormSubmit" value="Sign Up"/>
                        <a href="{{ route('login') }}" class="pull-right pd-tp-8">Log In</a>
                    </div>
                </div>
            </form>
            <div class="overlay hidden" id="SignupFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Signing up...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>
    </div>
@endsection

@section ('dynamicScripts')
    <script src="{{ asset('js/common.js') }}"></script>
    <script src="{{ asset('js/auth/signup.js') }}"></script>
@endsection
