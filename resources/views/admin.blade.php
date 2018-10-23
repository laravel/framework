@extends('layouts/master_template')

@section('content')

@if(session()->has("mobileValidation"))
    <div id="backgroundWrapper">
        <div class="row">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title no-capitalize">
                            <i class="icon fa fa-check"></i>Please validate your Mobile Number
                        </h4>
                    </div>
                    <div class="modal-body clearfix">
                        <form id="ValidateMobile" action="/activatemobile" method="post">
                            {{ csrf_field() }}
                            <div class="form-group has-feedback">
                                <input type="text" id="BlurNumber" class="form-control" value="{{$phoneNumber}}" disabled="disabled"/>
                                <span class="glyphicon glyphicon-phone form-control-feedback"></span>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="hidden form-control" id="InputBox" name="InputBox" placeholder="Enter OTP sent to your registered mobile" autocomplete="off" />
                                <span class="hidden glyphicon glyphicon-lock form-control-feedback"></span>
                            </div>
                            <div class="col-xs-3 pull-left" style="padding:0px">
                                <button type="submit" class="hidden btn btn-primary btn-block btn-flat" id="ContinueButton">Continue</button>
                            </div>
                            <div class="col-xs-5 pull-right text-right" style="padding-left:0;padding-top:5px;padding-right:0">
                                <a class="validate-mob-links hidden" id="CancelChangeMobile">Cancel</a>
                                <a class="validate-mob-links" id="ChangeMobile">Change Mobile</a>&nbsp;&nbsp;&nbsp;
                                <a class="validate-mob-links" id="ResendOTP">Resend OTP</a>
                            </div>

                        </form>
                    </div>
                    <div id="NotificationArea">
                        <div class="alert alert-dismissible hidden"></div>
                    </div>
                </div>
                <div class="form-loader hidden" id="ValidateMobileFormLoader"></div>
            </div>
        </div>
    </div>
@endif

@if($emailInfo->IsValidated != 1)
    <div class="callout callout-info">
        <h4>Reminder!</h4>
        <p>You are yet to validate your email.  If you did not receive it, please check your spam folder or <a id="resend-emailtoken" class="text-hover">click here</a> to resend email.</p>
    </div>
@endif

@if(isset($emailValidation))
    <div class="alert alert-dismissible alert-success" id="firstTimeEmailValidation">
        <strong><i class='icon fa fa-check'></i> Success : </strong>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        {{ $emailValidation }}
    </div>
@endif

<?php $Manager = false ?>
<?php $Designer = false ?>
@foreach(Auth::user()->Role()->get()->toArray() as $Key => $Role)
@if($Role["Slug"] === env("DESIGNER_ROLE_SLUG", "autocaddesigner"))
<?php $Designer = true ?>
@break
@endif
@if($Role["Slug"] === env("MANAGER_ROLE_SLUG", "manager"))
<?php $Manager = true ?>
@endif
@endforeach

@if(!$Manager && !$Designer)
@include('customerDashboard')
@else
@include('adminDashboard')
@endif
@endsection

@section('dynamicScripts')
<script type="text/javascript" src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script type="text/javascript" src="{{ URL::assetUrl('/js/dashboard.js') }}"></script>
@if(session()->has("mobileValidation"))
<script src="{{ URL::assetUrl('/validation/jquery.validate.min.js') }}" charset="utf-8"></script>
<script src="{{ URL::assetUrl('/js/common.js') }}" charset="utf-8"></script>
<script src="{{ URL::assetUrl('/js/validateMobile.js') }}" charset="utf-8"></script>
@endif
@if(isset($emailValidation))
<script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
        setTimeout(clearNotificationMessage, 10000);
    });

    function clearNotificationMessage() {
        $("#firstTimeEmailValidation").addClass('hidden');
    }
</script>
@endif
@endsection
