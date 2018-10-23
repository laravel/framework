@component('mail::message')
Greetings from {{ $title }} team.Thanks for filling up the Quick Estimate Checklist. Our CRM team will get in touch with you shortly.<br/>

A copy of the same has been enclosed with this email for your reference. Please quote the QEC ref. no. <b> {{ $refno }} </b> while having any communication with our CRM team.<br/>

You can reach us on {{ $contactnumber }} or reply to this email for any further information.<br/>

Regards,<br/>
CRM Team<br/>
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
