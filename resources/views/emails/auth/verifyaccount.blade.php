@component('mail::message')
# Dear {{ $name }},

Thank you for showing interest in {{ $team }}. You have reached the right destination for one stop end to end House Interiors.<br/>

To know more about us and our brand, Please go through the attached quick video and FAQ. We are happy to assist you with any questions.<br/>


Click the button below to validate your email.

@component('mail::button', ['url' => $validationLink])
Validate Email
@endcomponent

## OR

Copy and paste the following link into your browser: <br/>
<{{ $validationLink }}>

We believe in making the process of budgeting and estimation transparent with you. Hence requesting you to fill in the Enquiry form and Quick estimates here:- <br>
Enquiry : <{{ $enquiryurl }}> <br>
Quick Estimate : <{{$qeurl }}>

Thanks,<br/>
{{ $team }} <br/>
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
