@component('mail::message')
# Dear  Team,

A new enquiry has been received. The details of the customer is as below: <br/>
<p>
    Name: {{ $name }}<br/>
    Location: <span style="text-transform: capitalize;">{{$location}}</span><br/>
    Mobile: {{ $CustomerMobile }}<br/>
    Email: {{ $CustomerEmail }}<br/>
    Project Name: <span style="text-transform: capitalize;">{{ $projectName }}</span><br/>
    Builder Name: <span style="text-transform: capitalize;">{{ $builderName }}</span><br/>
    Unit Type: {{ $unitType }}<br/>
    Unit: {{ $unit }}<br/>
    Super Built up: {{ $superBuildupArea }}
    <br/>
</p>

Click the button below to view or update the Enquiry.

@component('mail::button', ['url' => $ViewEnquiryLink])
View
@endcomponent

## OR

Copy and paste the following link into your browser:<br/>
<{{ $ViewEnquiryLink }}>


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