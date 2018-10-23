<?php
$CurrentDomainDetailsView = App\HTTP\Models\Client::CurrentDomain()->get()->first();
$CurrentDomainSettingsView = json_decode($CurrentDomainDetailsView->SettingJson);
$RandomImageArray = json_decode(json_encode($CurrentDomainSettingsView->BackgroundSliderImages), true);
$RandomImageIndex = array_rand($RandomImageArray, 1);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ $page_title ?? "Login" }} - {{ $CurrentDomainDetailsView->Name }}</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
        <link rel="shortcut icon" type="image/png" href="{{ URL::CDN("$CurrentDomainSettingsView->FaviCon")}}" />
        @include('layouts/partials/styleSheets')
        @yield('dynamicStyles')
        <link rel="stylesheet" href="{{ URL::assetUrl("/css/homepageslider.css") }}">
    </head>
    <body class="hold-transition login-page">
        <ul class="cb-slideshow">
            @foreach( $CurrentDomainSettingsView->BackgroundSliderImages as $BgImage )
            @if($loop->first)
            <li><span style="background-image: url({{ URL::CDN($RandomImageArray[$RandomImageIndex]['URL']) }})"></span></li>
            @else
            <li><span style="background-image: url({{ URL::CDN($BgImage->URL) }})"></span></li>
            @endif
            @endforeach
        </ul>
        <section class="content">
            @yield('content')
        </section>
        @include('layouts/partials/scriptSource')
        <script type="text/javascript">
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>
        <?php echo "<script id='errorListScript'>var AlertData = " . json_encode(Cache::get("NotificationData")) . ";</script>"; ?>
        @yield('dynamicScripts')
        <!-- Google Analytics script -->
        <script src="{{ URL::assetUrl("/js/GoogleAnalytics.js") }}"></script>
    </body>
</html>
