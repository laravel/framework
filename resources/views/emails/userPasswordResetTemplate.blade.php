@extends("layouts.EmailTemplateLayout")

@section("pageTitle", "Password reset link - {{ $SiteTitle }}")

@section("content")
<p style="text-transform: capitalize;"><b>Dear {{ $firstName }},</b></p>

<p>Greetings from {{ $SiteTitle }} team. It is our pleasure to fulfill your request for new password.</p>
<p>To change your customer account password at {{ $SiteTitle }}, please <a target="_blank" href="{{ $validationLink }}">click here</a> </p>
<p><b>OR</b></p>
<p>Copy and paste the following link into your browser:</p>
<p><a target="_blank" href="{{ $validationLink }}"> {{ $validationLink }} </a></p>
@endsection

@section("footer")
@include('layouts.EmailTemplateFooter')
@endsection