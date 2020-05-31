## Laravel ajax system example

the laravel ajax system is an library to make easy ajax in the laravel




#### resources/views/login.blade.php:
```html
<?php \Illuminate\Ajax\Ajax::show_script(); // this code shows client side js code for laravel ajax ?>
<!--- an ajax login form example --->
<input type="text" id="username" />
<input type="password" id="password" />
<button onclick="larajax('btn_login' , {username: '#username' , password: '#password'})">Login</button>
```




#### routes/web.php:
```php
// you another codes...


use Illuminate\Ajax\Ajax;

// create a new ajax event using make function
// first argument is name of event
// secound is action closure
// closure gets $res
Ajax::make('btn_login' , function($res){
    $username = $res->data('username');
    $password = $res->data('password');
    // do login process...
    if($login_success){
        return $res->alert('hello ' . $username);
    }
    return $res->alert('login faild');
});

Ajax::set_handler_route('/ajax-handler');

```
