@component('mail::message')
# Dear {{ $name }},

Greetings from {{ $team }} team. <b> {{ $fromName }} </b> has commented on Design.<br/>


##{{ $emailTitle }}
<p>
    {{ $emailBody }}
</p>



Click the button below to view or update the Design.

@component('mail::button', ['url' => $designLink])
View
@endcomponent

## OR

Copy and paste the following link into your browser:<br/>
<{{ $designLink }}>

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
