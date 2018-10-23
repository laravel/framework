@component('mail::message')
# Dear  Team,

A new Quick Estimate Checklist has been received. The details of the customer are as below: <br/>

<p>
    Name: {{ $name }}<br/>
    Mobile: {{ $mobile }}<br/>
    Email: {{ $email }}<br/>
    QEC ref. no.: {{ $refno }}<br/>
</p>

Click the button below to view or update the Quick Estimate.

@component('mail::button', ['url' => $refLink])
View
@endcomponent

## OR

Copy and paste the following link into your browser:<br/>
<{{ $refLink }}>


Regards,<br/>
CRM Team<br/>
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
