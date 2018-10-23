
@component('mail::message')
Greetings from {{ $title }} team.Your enquiry with Reference number <b>{{ $ReferenceNo }} </b> has been updated by {{ $title }}.<br/>

Click the button below to view  the Enquiry.

@component('mail::button', ['url' => $refLink])
View
@endcomponent

## OR

Copy and paste the following link into your browser: <br/>
<{{ $refLink }}>

Thanks,<br/>
{{ $title }}<br/>

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
