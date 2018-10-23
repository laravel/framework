<div id="MobileValidation">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title no-capitalize no-text-selection">Validate your Mobile</h4>
            </div>
            <div class="modal-body clearfix">
                <form id="MobileValidationForm" method="POST" data-validate-mobile="{{ route('mobile.otp') }}" data-change-mobile="{{ route('mobile.change') }}">
                    {{ csrf_field() }}
                    <div class="form-group has-feedback">
                        <input type="text" id='MobileDisplay' class="form-control" value="XXXXXX{{ substr($mobile->Phone, -4) }}" disabled="disabled"/>
                        <span class="fa fa-phone form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback hidden">
                        <input type="text" id="OTP" name="OTP" class="form-control" placeholder="Enter OTP sent to your registered mobile" autocomplete="off"/>
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback hidden">
                        <input type="text" id="Mobile" name="Mobile" class="form-control" placeholder="Enter a new mobile to replace old one" autocomplete="off" data-entity-existence-url="{{ route('check.mobile') }}"/>
                        <span class="fa fa-phone form-control-feedback"></span>
                    </div>
                    <div class="col-xs-12 col-sm-2 pull-left pd-0">
                        <button type="submit" class="btn btn-primary btn-flat btn-block hidden" id="MobileValidationFormSubmit">Continue</button>
                    </div>
                    <div class="col-xs-12 col-sm-10 pd-rt-0 pd-tp-8 validate-mob-links">
                        <a class="validate-mob-link hidden" id="CancelChangeMobile">Cancel</a>
                        <a class="validate-mob-link" id="ChangeMobile">Change Mobile</a>
                        <a href="{{ route('mobile.otp.resend') }}" class="validate-mob-link pd-lt-8" id="ResendOTP">Resend OTP</a>
                    </div>
                </form>
            </div>
            <div id="NotificationArea"></div>
            <div class="overlay hidden" id="MobileValidationFormOverlay">
                <div class="large loader"></div>
                <div class="loader-text">Updating Mobile...</div>
            </div>
        </div>
    </div>
</div>
