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
        <div class="box box-primary pd-0" style="page-break-inside: avoid; font-size: 11px !important;">
            <div class="row pd-0">
                <div class="col-xs-4">
                     <?php echo $FooterLogo; ?>
                </div>
                <div class="col-xs-7 text-right">
                    <p class="mr-0">For details visit {{URL::route('enquiries.quick-estimates.show',['enquiry'=>$FooterData["EnquiryReferenceNumber"],'estimate'=>$FooterData["QuickEstReferenceNumber"]])}}</p>   
                </div>
                <div class="col-xs-1" style="border-left: 1px solid; font-size: 9px !important;">
                    <p class="pull-left mr-0" style="width: 120px !important;">            
                    &nbsp;Page <span class="page"></span> of <span class="topage"></span></p>                    
                </div>
            </div>
        </div>
    </body>
</html>

