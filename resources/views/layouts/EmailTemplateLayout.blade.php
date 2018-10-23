<!DOCTYPE html>
<html>
    <head>
        <title>@yield("pageTitle")</title>
        <meta charset="utf-8">
    </head>
    <body>
        <div style="padding:1em;background-color:#ddd;text-align:center">
            <img src='{{URL::CDN("$HeaderLogo")}}' alt="{{ $SiteTitle }}">
        </div>
        <div style="padding:1em;padding-left:3em">
            @yield("content")
            @yield("footer")
        </div>
        <div class="social-links">
            <a href="#"><i class="glyphicon glyphicon-facebook fa-lg"></i></a>
            <a href="#"><i class="glyphicon glyphicon-google-plus fa-lg"></i></a>
            <a href="#"><i class="glyphicon glyphicon-pinterest fa-lg"></i></a>
        </div>
    </body>
</html>