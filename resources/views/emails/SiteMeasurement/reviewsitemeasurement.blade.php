@component('mail::message')
# Dear {{ $name }},

Greetings from {{ $team }} team. Site Measurement with id <strong>{{ $siteId }}</strong> has been come for review.

Click the button below to review the Site Measurement.

@component('mail::button', ['url' => $siteMeasurementLink])
Review
@endcomponent

## OR

Copy and paste the following link into your browser:<br/>
<{{ $siteMeasurementLink }}>

Regards,<br/>
Support Team<br/>
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
