<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Main View

Route::get('/', function () {
    return view('test');
});

// Login View

Route::get('/login', function(){
	return view('login');
});

// Orders View

Route::get('/orders', function(){
	return view('orders');
});

// Inventory View

Route::get('/inventory', function(){
	return view('inventory');
});

// Profile View

Route::get('/profile', function(){
	return view('profile');
});
