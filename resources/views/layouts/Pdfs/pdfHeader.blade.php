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
        <?php include(public_path('/assets/css/app.css')); ?>
        </style>
    </head>
    <body> 
        <div class="box box-primary">
            <div class="pd-tp-10 pd-bt-5"> 
                <?php $HeaderLogo = $Header['Logo']; ?>
            <img width="210px" src='{{URL::CDN("$HeaderLogo")}}'>
        </div>
        </div>
    </body>
</html>