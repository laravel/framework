@extends('layouts/auth_template')

@section('dynamicStyles')
    <link rel="stylesheet" href="{{ URL::assetUrl('/css/custome.css') }}" media="screen" title="no title" charset="utf-8">
@endsection

@section('content')
    <div class="register-box">
        @if(session('returnMessage'))
            <?php $messageData = ['error' => ['cssClass' => 'error', 'iconType' => 'ban'], 'success' => ['cssClass' => 'success', 'iconType' => 'check'], 'warning' => ['cssClass' => 'warning', 'iconType' => 'warning'], 'info' => ['cssClass' => 'info', 'iconType' => 'info']] ?>
            <div class="alert alert-{{$messageData[session('msgType')]['cssClass']}}">
                <strong id="alert-title"><i class="icon fa fa-{{$messageData[session('msgType')]['iconType']}}"></i> {{session('alertTitle')}} : </strong>
                <span id="alert-data">{{session('alertMessage')}}</span>
            </div>
        @endif
        <div class="register-box-body">
             @include('/layouts/partials/LogoHeader')
            @if (session()->has('error'))
                <label class="error">{{ session('error') }}</label>
            @endif
            <form name='RegistrationForm' id='RegistrationForm' action="{{ URL::asset('/registration') }}" method="post">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <label for="FirstName">First name</label>
                    <input name="FirstName" id="FirstName" type="text" class="form-control @if($errors->has("FirstName")) error @endif" placeholder="Ex: John" value="{{ old('FirstName') }}">
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                    @if($errors->has("FirstName"))
                        <label id="FirstName-error" class="error" for="FirstName">{{ $errors->first("FirstName") }}</label>
                    @endif
                </div>
                <div class="form-group has-feedback">
                    <label for="LastName">Last name</label>
                    <input name="LastName" id="LastName" type="text" class="form-control @if($errors->has("LastName")) error @endif" placeholder="Ex: Doe" value="{{ old('LastName') }}">
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                    @if($errors->has("LastName"))
                        <label id="LastName-error" class="error" for="LastName">{{ $errors->first("LastName") }}</label>
                    @endif
                </div>
                <div class="form-group has-feedback">
                    <label for="PhoneNumber">Mobile number</label>
                    <input name="PhoneNumber" id="PhoneNumber" type="text" class="form-control @if($errors->has("PhoneNumber")) error @endif" placeholder="Ex: (999) 999-9999" value="{{ old('PhoneNumber') }}">
                    <span class="glyphicon glyphicon-phone form-control-feedback"></span>
                    @if($errors->has("PhoneNumber"))
                        <label id="PhoneNumber-error" class="error" for="PhoneNumber">{{ $errors->first("PhoneNumber") }}</label>
                    @endif
                </div>
                <div class="form-group has-feedback">
                    <label for="Email">Email</label>
                    <input name="Email" id="Email" type="email" class="form-control @if($errors->has("Email")) error @endif" placeholder="Ex: user@example.com" value="{{ old('Email') }}"/>
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                    @if($errors->has("Email"))
                        <label id="Email-error" class="error" for="Email">{{ $errors->first("Email") }}</label>
                    @endif
                </div>
                <div class="form-group has-feedback">
                    <label for="Password">Password</label>
                    <input name="Password" id="Password" type="password" class="form-control @if($errors->has("Password")) error @endif" placeholder="Password"/>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    @if($errors->has("Password"))
                        <label id="Password-error" class="error" for="Password">{{ $errors->first("Password") }}</label>
                    @endif
                </div>
                <div class="form-group has-feedback">
                    <label for="Password_confirmation">Confirm Password</label>
                    <input name="Password_confirmation" id="Password_confirmation" type="password" class="form-control @if($errors->has("Password_confirmation")) error @endif" placeholder="Confirm Password">
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    @if($errors->has("Password_confirmation"))
                        <label id="Password_confirmation-error" class="error" for="Password_confirmation">{{ $errors->first("Password_confirmation") }}</label>
                    @endif
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <button type="submit" class="btn btn-primary btn-flat" id="registerFormSubmit">Sign Up</button>
                        <a href="{{ route('login') }}" class="text-center fl-rt text-capitalize" style="margin-top: 0.4em; margin-right:1em;">Log in</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="alert hidden" id="formAlert">
            <strong id="alert-title"></strong>
            <span id="alert-data"></span>
        </div>
    </div>
@endsection
@include('layouts/partials/termsConditions')
@section('dynamicScripts')
    <script src="{{ URL::assetUrl('/validation/jquery.validate.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/validation/additional-methods.min.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/common.js') }}"></script>
    <script src="{{ URL::assetUrl('/js/registration.js') }}"></script>
@endsection
