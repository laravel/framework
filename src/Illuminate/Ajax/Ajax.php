<?php

namespace Illuminate\Ajax;

use Illuminate\Support\Facades\Route;

class Ajax{

    /*
     * @var array
     */
    protected static $events = [];


    /*
     * @var string
     */
    protected static $handler_route;

    /*
     * set the new ajax event
     * 
     * @param  string   $name
     * @param  \Closure $action
     */
    public static function make($name , \Closure $action){
        static::$events[$name] = $action;
    }


    /*
     * set the route of ajax handler
     * 
     * @param  string  $route
     */
    public static function set_handler_route(string $route){
        static::$handler_route = $route;
        Route::get($route , function(){
            return \Illuminate\Ajax\Ajax::handle(request());
        });
    }


    public static function handle($r){
        // handle the requested ajax event from client
        $event = $r->get('_event');
        if(!isset(static::$events[$event])){
            // event does not exist
            echo 'not found';
            return;
        }

        // listing the recived data from client
        $data = [];
        foreach($_GET as $key => $value){
            if($key != '_event'){
                $data[$key] = $value;
            }
        }

        // run the event action
        $action = static::$events[$event];
        $response = $action(new AjaxResponse($data));

        return $response->render();
    }



    /*
     * show the js client script
     * 
     */
    public static function show_script($url=null){
        if($url === null){
            $url = static::$handler_route;
        }
        $script = '
        function larajax(name , data=[]){
            var url = "'.$url.'?_event=" + name;
            for(var key in data){
                var val = document.querySelector(data[key]).value;
                url += "&" + key + "=" + val;
            }
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                eval(this.responseText);
                }
            };
            xhttp.open("GET", url, true);
            xhttp.send();
        }
        ';

        $script = '<script> ' . $script . ' </script>';
        echo $script;
    }
}

