@extends ('layouts.master_template')

@section ('content')
    {{-- show mobile validation modal, if mobile is not validated --}}
    @if (! $mobile->isValidated())
        @include ('home.partials.mobilevalidation')
    @endif

    {{-- show email validation reminder, if email is not validated --}}
    @if (! $email->isValidated())
        <section id="ValidateEmailReminder">
            <div class="callout callout-info">
                <h4>Reminder!</h4>
                <p>You are yet to validate your email.  If you did not receive it, please check your spam folder or <a href="{{ route('dashboard.verifyemail.resend') }}" id="ResendEmailToken" class="text-hover">click here</a> to resend email.</p>
            </div>
            <div class="overlay hidden" id="ResendEmailTokenOverlay">
                <div class="large loader"></div>
            </div>
        </section>
    @endif

    {{-- show email validation success message, if email is validated --}}
    @if (session()->has("emailHasValidated"))
        <div class="alert alert-{{ session()->get('emailHasValidated.type') }} alert-dismissible" id="ValidatedEmailAlert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            {{ session()->get('emailHasValidated.body') }}
        </div>
    @endif

    {{-- show dashboards according to user roles --}}
    @if ($user->isManager())
        @include('home.dashboards.manager')
    @elseif ($user->isDesigner())
        @include('home.dashboards.designer')
    @elseif ($user->isCustomer())
        @include('home.dashboards.customer')
    @elseif ($user->isSupervisor())
        @include('home.dashboards.supervisor')
    @elseif ($user->isReviewer())
        @include('home.dashboards.reviewer')
    @elseif ($user->isApprover())
        @include('home.dashboards.approver')
    @elseif ($user->isSales())
        @include('home.dashboards.sales')
    @elseif ($user->isDataManager())
        @include('home.dashboards.datamanager')
    @endif
@endsection

@section ('dynamicStyles')
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/magnific-popup/magnific-popup.min.css') }}"/>
@endsection

@section ('dynamicScripts')
    <script type="text/javascript" src="{{ asset('plugins/magnific-popup/magnific-popup.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/dashboard.js') }}"></script>

    {{-- Add additional js for mobile validation --}}
    @if (! $mobile->isValidated())
        <script type="text/javascript" src="{{ asset('js/common.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/dashboard/validatemobile.js') }}"></script>
    @endif

    {{-- Add additional js for email validation reminder --}}
    @if (! $email->isValidated())
        <script type="text/javascript" src="{{ asset('js/dashboard/validateemail.js') }}"></script>
    @endif

    {{-- include js to hide email validation success message --}}
    @if (session()->has("emailHasValidated"))
        <script type="text/javascript">
            $(document).ready(function() {
                setTimeout(function () {
                    $("#ValidatedEmailAlert").fadeOut('slow', function () {
                        $(this).addClass('hidden');
                    });
                }, 10000);
            });
        </script>
    @endif
@endsection
