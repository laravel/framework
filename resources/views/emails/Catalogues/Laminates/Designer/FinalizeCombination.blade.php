@component('mail::message')
# Dear {{ $name }},

Greetings from {{ $title }} team. {{ $fromName }} has been finalized the  Laminate selection with the status of 
@if($status == 4) <b>Approved</b>
@elseif($status == 3) <b>On Hold</b>
@elseif($status == 5) <b>Needs Discussion</b>
@endif

Click the button below to view the shortlisted laminate selection.

@component('mail::button', ['url' => $LaminateSelectionsLink])
Review
@endcomponent

## OR

Copy and paste the following link into your browser:<br/>
<{{ $LaminateSelectionsLink }}>

Regards,<br/>
Support Team<br/>
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
