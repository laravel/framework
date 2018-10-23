@extends('layouts/auth_template')

@section('content')

    <div class="login-box">
        @include('/layouts/partials/LogoHeader')
        @if (session('returnMessage'))
            <?php $messageData = ['error' => ['cssClass' => 'danger', 'iconType' => 'ban'], 'success' => ['cssClass' => 'success', 'iconType' => 'check'], 'warning' => ['cssClass' => 'warning', 'iconType' => 'warning'], 'info' => ['cssClass' => 'info', 'iconType' => 'info']] ?>
            <div class="alert alert-{{$messageData[session('msgType')]['cssClass']}}">
                <strong id="alert-title"><i class="icon fa fa-{{$messageData[session('msgType')]['iconType']}}"></i> {{session('alertTitle')}} : </strong>
                <span id="alert-data">{{session('alertMessage')}}</span>
            </div>
        @endif
        <div class="login-box-body">
            <p class="login-box-msg">Enter the new password to Log In with</p>

            @if (session()->has('error'))
                <label class="error">{{ session('error') }}</label>
            @endif

            @if (session()->has('msg'))
                <label class="success">{{ session('msg') }}</label>
            @endif

            <form id="CreateNewPasswordForm" name="CreateNewPasswordForm" action="{{ URl::asset("/$formURL")}}" method="post">
                {{ csrf_field() }}
                @if(!isset($type))
                    <input type="hidden" name="tokenid" value="{{$tokenid}}"/>
                    <input type="hidden" name="validationtoken" value="{{$validationtoken}}"/>
                @endif
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
                    <div class="col-xs-12 col-sm-6">
                        <button type="submit" class="btn btn-primary btn-block btn-flat" id="continue">Update Password</button>
                    </div><!-- /.col -->
                </div>
            </form>
        </div>
        <!-- /.login-box-body -->
    </div>
    <!-- /.login-box -->
@endsection

@section('dynamicScripts')
    <script src="{{ URL::asset("/validation/jquery.validate.min.js") }}" charset="utf-8"></script>
    <script src="{{ URL::asset("/validation/additional-methods.min.js") }}" charset="utf-8"></script>
    <script src="{{ URL::asset("/js/common.js") }}" charset="utf-8"></script>
    <script src="{{ URL::asset("/js/createNewPassword.js") }}" charset="utf-8"></script>
@endsection
