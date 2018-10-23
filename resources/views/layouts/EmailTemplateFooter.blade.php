<p>Regards,<br/>
    Support Team<br/>
    {{ $SiteTitle }}<br>
    <img style="height: 28px; width: 125px;margin-top:0.8em" src="{{ URL::CDN($FooterLogo)}}" alt="{{ $SiteTitle }}"><br>
    <a href="http://{{$ClientUrl}}">{{$ClientUrl}}</a><br><br>
    @foreach($CurrentDomainSettingsView->Settings->Social as $Icons)
    <a href="http://{{ $Icons->url }}" target="_blank"><img src="{{ URL::CDN($Icons->src) }}" alt="{{ $SiteTitle }}" style="width:25px;height:25px;"></a>
    @endforeach     
</p>
<b style="font-family:arial;font-size:11px">
    <div>
        <b style="font-family:arial;font-size:11px">
            <br>
        </b>
    </div>
    Note:
</b>
<span style="font-family:arial;font-size:11px"> </span>
<i style="font-family:arial;font-size:11px">Please do not reply to this email. It has been sent from an email account that is not monitored.</i>
<br>
<p style="font-family: arial; font-size: 11px;"><i>To ensure that you receive communication related to your request from <a href="http://{{$ClientUrl}}">{{$ClientUrl}}</a>, please add {{$replyEmail}} to your contact list/address book.</i></p>