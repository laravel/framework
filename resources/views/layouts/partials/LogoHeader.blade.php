<div class="login-logo">
    <a href="{{ URL::route('dashboard') }}">
       <?php $CurrentDomainDetailsView = App\Http\Models\Client::CurrentDomain()->get()->first();
       $CurrentDomainSettingsView = json_decode($CurrentDomainDetailsView->SettingJson); ?>
       <object class="logo-large" data="{{ URL::CDN("$CurrentDomainSettingsView->SiteLogo")}}">
            {{ $CurrentDomainDetailsView->Name }}
        </object>
    </a>
</div>