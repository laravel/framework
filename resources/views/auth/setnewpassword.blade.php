@extends ('layouts/auth_template')

@section ('content')
    <div class="login-box">
        @if (session()->has('newPasswordMessage'))
            <div class="alert alert-{{ session('newPasswordMessage.type') }}">
                <p class="body">{{ session('newPasswordMessage.body') }}</p>
            </div>
        @endif
        <div class="login-box-body">
            @include('/layouts/partials/LogoHeader')
            <form id="CreateNewPasswordForm" name="CreateNewPasswordForm" action="{{ route('setnewpassword') }}" method="POST">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="Password">Password</label>
                    <input type="password" class="form-control" placeholder="New Password" name="Password" id="Password"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <label for="ConfirmPassword">Confirm password</label>
                    <input type="password" class="form-control" placeholder="Confirm Password" name="ConfirmPassword" id="ConfirmPassword"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-5">
                        <input type="submit" class="btn btn-primary btn-block btn-flat" id="CreateNewPasswordFormSubmit" value="Update"/>
                    </div>
                </div>
            </form>
            <div class="overlay hidden" id="CreateNewPasswordFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating...</div>
            </div>
        </div>
        <div id="NotificationArea"></div>
    </div>
@endsection

@section('dynamicScripts')
    <script src="{{ asset("validation/jquery.validate.min.js") }}" charset="utf-8"></script>
    <script src="{{ asset("js/common.js") }}" charset="utf-8"></script>
    <script src="{{ asset("js/auth/setnewpassword.js") }}" charset="utf-8"></script>
@endsection
