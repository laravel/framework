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
        // after click the Login btn, this function will be run and if login_success
        // an alert will be show to the user and next user will be
        // redirect to the /dashboard url
        return $res->alert('hello ' . $username)->redirect('/dahsboard');
    }
    return $res->alert('login faild');
});

Ajax::set_handler_route('/ajax-handler');

```


### another `$res` methods:

| Method            |  Description                  |
|-------------------|:-----------------------------:|
| `alert($message)` |  shows javascript alert       |
| `redirect($url)`  |    redirect user to the url   |


new methods will be add...

