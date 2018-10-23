@component('mail::message')
# Dear {{ $name }},

Greetings from {{ $team }} team. It is our pleasure to fulfill your request for new password.<br/>

To change your customer account password at HECHPE Spaces, please click on Set new password button.

@component('mail::button', ['url' => $resetLink])

Set new password

@endcomponent

## OR

Copy and paste the following link into your browser:<br/>
<{{ $resetLink }}>

Thanks,<br/>
{{ $team }}<br/>

![{{ $team }}]({{ cdn_asset($footerLogo)}} "{{ $team }}")<br/>
<{{ $url }}>
@foreach($socials as $social)
	<a href="{{ $social->url }}">
		<img src="{{ cdn_asset($social->src) }}" alt="{{ $social->title }}" title="{{ $social->title }}"/>
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
