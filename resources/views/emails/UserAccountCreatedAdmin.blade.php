@component('mail::message')
# Dear  {{ $name }},
Greetings from {{ $team }}. Thanks for registering with us.<br/>

Click the button below to start accessing your account.

@component('mail::button', ['url' => $Link])
Set Password
@endcomponent

to create a new password for your {{ $team }} account <br/>

## OR

Copy and paste the following link in your browser:<br/>
<{{ $Link }}>

Your username is :  {{ $email }} ?? {{ $mobile }}

Thanks,<br/>
{{ $team }}<br/>

![{{ $team }}]({{ URL::CDN($footerLogo)}} "{{ $team }}")<br/>
<{{ $url }}>
@foreach($socials as $social)
    <a href="{{ $social->url }}">
        <img src="{{ URL::CDN($social->src) }}" alt="{{ $social->title }}" title="{{ $social->title }}"/>
    </a>
@endforeach

<p>
    <small>
        <b>Note:</b>
        <i>Please do not reply to this email. It has been sent from an email account that is not monitored.</i><br/>
        <i>To ensure that you receive communication related to your request from <a href="http://{{$url}}">{{$url}}</a>, please add {{ $from }} to your contact list/address book.</i>
    </small>
</p>
@endcomponent