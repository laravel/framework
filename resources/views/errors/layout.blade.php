<?php
if (!defined('CURRENT_DOMAIN')) {
$CurrentDomain = $_SERVER['HTTP_HOST'];
define('CURRENT_DOMAIN', $CurrentDomain);
}
        
$CurrentDomainDetailsView = App\Http\Models\Client::CurrentDomain()->get()->first();
$CurrentDomainSettingsView = json_decode($CurrentDomainDetailsView->SettingJson);
?>
<!DOCTYPE html>
<html>
<head>
    <title>@yield("title") - {{ $CurrentDomainDetailsView->Name }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ URL::CDN("$CurrentDomainSettingsView->FaviCon")}}"/>
    <link rel="stylesheet" type="text/css" href="{{asset('plugins/font-awesome/css/font-awesome.min.css')}}"/>
    <link rel="stylesheet" type="text/css" href="{{asset('css/errors.css')}}"/>
</head>
<body>
    <section class="app">
        @yield("content")
    </section>
</body>
</html>
