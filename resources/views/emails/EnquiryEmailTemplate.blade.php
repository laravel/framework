
@component('mail::message')
# Dear {{ $name }},

Thanks for your time for completing the Enquiry Form. If you want to come onboard with us, Please drop an email at <support@hechpe.com> or call us @ {{ $contactnumber }}<br/>

Attached is a copy of your submitted Enquiry Form. We are happy to assist you with any questions. <br/>

We believe in making the process of budgeting and estimation transparent with you. Hence requesting you to fill in the High level requirements here:- <br/>

{{ $qeurl }}

This will give you an idea of our budgeting as per your requirements. <br/>

Looking forward to our collaboration. Have a good day !!<br/>

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