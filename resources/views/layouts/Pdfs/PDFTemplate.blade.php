<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport" />
        <style>
            <?php include(public_path('AdminLTE/bootstrap/css/bootstrap.min.css')); ?>
            <?php include(public_path('css/custome.css')); ?>
            <?php include(public_path('css/quickestimate/create.css'));?>
            <?php include(public_path('assets/css/app.css')); ?>
        </style>
    </head>
    <body>     
        <div class="wrapper">
            <div class="content-wrapper">             
                <section class="content">
                    @yield('content')
                </section>
            </div>
        </div>
    </body>
</html>
