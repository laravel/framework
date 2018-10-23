<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
        <style>
        <?php include(public_path('/AdminLTE/bootstrap/css/bootstrap.min.css')); ?>
        <?php include(public_path('/css/custome.css')); ?>
        <?php include(public_path('/assets/css/app.css')); ?>
        .header-info{
            font-size: 12px;
            margin: 0px;
        }
        </style>
    </head>
    <body> 
        <div class="box box-primary">
            <div class="row pd-tp-10 pd-rt-5">
                <div class="col-xs-4">
                    <div class="row">
                        <div class="col-xs-8 mr-rt-0">
                            <?php $HeaderLogo = $Header['Logo']; ?>
                            <img width="210px" src='{{URL::CDN("$HeaderLogo")}}'>
                        </div>
                        <div class="col-xs-4 br-lt pd-0">
                            <h3 class="mr-lt-5 mr-tp-9">Estimate</h3>
                        </div>
                    </div>
                </div>
                <div class="col-xs-8">
                    <p class="header-info text-right">{{ $HeaderData['QuickEstDeatils']->Name }} ({{ $HeaderData['QuickEstDeatils']->ReferenceNumber }}) | {{ $HeaderData['EnquiryName'] }} ({{ $HeaderData['EnquiryNo'] }})</p>
                    <p class="header-info text-right">{{ $HeaderData['CustomerFullName'] }},
                        {{ $HeaderData['CustomerMobile'] }},
                        {{ $HeaderData['CustomerEmail'] }}
                    </p>
                    <p class="header-info text-right">{{$HeaderData['SuperBuiltUpArea']}} Sqft,
                        {{ $HeaderData['UnitType'] }},
                        {{ $HeaderData['ProjectName'] }},
                        {{ $HeaderData['QuickEstDeatils']->City->Name }}
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>