<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
        <style>
<?php include(public_path('AdminLTE/bootstrap/css/bootstrap.min.css')); ?>
<?php include(public_path('css/custome.css')); ?>
<?php include(public_path('assets/css/app.css')); ?>
        </style>
            <script>
        function substitutePdfVariables() {

            function getParameterByName(name) {
                var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
                return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
            }

            function substitute(name) {
                var value = getParameterByName(name);
                var elements = document.getElementsByClassName(name);

                for (var i = 0; elements && i < elements.length; i++) {
                    elements[i].textContent = value;
                }
            }

            ['frompage', 'topage', 'page', 'webpage', 'section', 'subsection', 'subsubsection']
                .forEach(function(param) {
                    substitute(param);
                });
        }
    </script>
    </head>
    <body onload="substitutePdfVariables()"> 
        <?php
        preg_match_all('!\d+!', $Footer['Logo'], $matches);
        $FooterLogo = str_replace($matches[0][0], date('Y'), $Footer['Logo']);
        ?>
        <div class="box box-primary pd-10" style="page-break-inside: avoid;">
            <div class="text-center">
                <?php echo $FooterLogo; ?>
                <p class="pull-right" style="width: 120px;">Page <span class="page"></span> of <span class="topage"></span></p>
            </div>
        </div>
    </body>
</html>

