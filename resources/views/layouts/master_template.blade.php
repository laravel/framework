<?php
$dbPictureName = Auth::User()->Person->Photo;
$defaultImage = URL::CDN("public/images/user-160x160.png");
if ($dbPictureName) {
    $splittedFileName = explode(".", $dbPictureName);
    if ($splittedFileName[0] === $dbPictureName) {
        $profilePictureURL = $defaultImage;
    } else {
        $profilePicture = $splittedFileName[0] . "-160x160." . $splittedFileName[1];
        $profilePicture = str_replace('/source/', '/thumbnails/', $profilePicture);
        if (Storage::has($profilePicture)) {
            $profilePictureURL = URL::CDN($profilePicture);
        } else {
            $profilePictureURL = $defaultImage;
        }
    }
} else {
    $profilePictureURL = $defaultImage;
}
$SidebarCollapseRoutes = ['quickestimate.create', 'quickestimate.show', 'quickestimate.duplicate', "ratecards.edit", "ratecards.list", "userenquiries", "search.enquiries", "enquiries.index", "searchquickestimation","quickestimate.list","estimate.create", "search.quickestimates", "quickestimates.list", "ratecards.reports.filter", "materials.list", "sitemeasurement.rooms.calculations", "catalogues.laminates.list", "catalogue.laminate.list", "designs.edit", "designs.show", "designs.designview", "users.enquiries.quick-estimates.create", "users.quick-estimates.index", "users.enquiries.quick-estimates.index", "users.enquiries.quick-estimates.show", "users.enquiries.quick-estimates.copy", "quick-estimates.index", "enquiries.quick-estimates.index", "enquiries.quick-estimates.create", "enquiries.quick-estimates.show", "enquiries.quick-estimates.copy",];

$CurrentDomainDetailsView = App\Http\Models\Client::CurrentDomain()->get()->first();
$CurrentDomainSettingsView = json_decode($CurrentDomainDetailsView->SettingJson);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ $page_title ?? null }} - {{ $CurrentDomainDetailsView->Name }}</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
        <link rel="shortcut icon" type="image/png" href="{{ URL::CDN("$CurrentDomainSettingsView->FaviCon") }}" />
        @include('layouts/partials/styleSheets')
        @yield('dynamicStyles')
    </head>
    <body class="hold-transition skin-black sidebar-mini {{ in_array(Route::currentRouteName(), $SidebarCollapseRoutes) ? 'sidebar-collapse' : '' }}">
        <div class="wrapper">
            @include('layouts/partials/header')
            @include('home.sidebar')
            <div class="content-wrapper clearfix">
                <section class="content-header">
                    <h2 class="no-margin"><small class="section-header">{{ $page_title ?? "Page Title" }}<span class="text-black">{{ isset($page_description) ? ": ".$page_description : null }}</span></small></h2>
                </section>
                <section class="content" id="App">
                    @yield('content')
                </section>
            </div>
            @include('layouts/partials/feedBackForm')
            @include('layouts/partials/footer')
        </div>
        <div class="page-overlay hidden" id="PageOverlay">
            <div class="close page-overlay-close">
                <i class="fa fa-times" aria-hidden="true"></i>
            </div>
            <div class="large loader"></div>
            <div class="loader-text"></div>
        </div>
        <div id="NotificationOverlay" class="page-overlay hidden">
            <div style="text-align: center;">
                <button type="button" aria-label="Close" title="Close" id="notif-overlay-close" class="close notificationOverlay-close">
                    <span aria-hidden="true">×</span>
                </button> 
                <div class="notification-icon"></div> 
                <div class="notification-message"></div>
            </div>
        </div>
        @yield('modalSection')
        <?php echo "<script>var AlertData = ".json_encode(Cache::get("NotificationData")).";</script>"; ?>
        @include('layouts/partials/scriptSource')
        <script type="text/javascript">
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $("#materials").on('click', function(event){
                window.location.href = $(this).attr('href');
            });
        </script>
        @yield('dynamicScripts')
        <!-- Google Analytics script -->
        <script src="{{ URL::assetUrl("/js/GoogleAnalytics.js") }}"></script>
        <script src="{{ asset("js/feedbackform.js") }}"></script>
    </body>
</html>
