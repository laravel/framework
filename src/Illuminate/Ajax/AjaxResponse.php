<?php

namespace Illuminate\Ajax;

class AjaxResponse{

    /*
     * 
     * @var array
     */
    protected $data;


    /*
     * response js code
     * 
     * @var string 
     */
    protected $response = '';


    function __construct($data){
        $this->data = $data;
    }


    /*
     * read the recived data by key
     * 
     * @param  string   $key
     * @param  var|null $default
     */
    public function data($key , $default=null){
        if(isset($this->data[$key])){
            return $this->data[$key];
        }

        return $default;
    }

    public function render(){
        return $this->response;
    }

    


    /*
     * show js alert
     * 
     * @param  string  $message
     */
    public function alert($message){
        $message = addslashes($message);
        $this->response .= "
alert('{$message}')";

        return $this;
    }


    /*
     * redirect user
     * 
     * @param  string  $url
     */
    public function redirect($url){
        $this->response .= "
location.href = '{$url}'";

        return $this;
    }
}

