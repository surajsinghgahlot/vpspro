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

    Auth::routes();

    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/home', 'HomeController@index')->name('home');

    Route::get('/profile', 'HomeController@profile')->name('profile');
    Route::put('/update/profile', 'HomeController@updateProfile')->name('update.profile');
    Route::put('/change/password', 'HomeController@changePassword')->name('change.password');
    Route::put('/change/default/password', 'HomeController@changeDefaultPassword')->name('change.default.password');
    
    /**
     * User Route's
     */
    Route::get('users', 'UserController@index')->name('index.user');
    Route::get('create/user', 'UserController@create')->name('create.user');
    Route::post('store/user', 'UserController@store')->name('create.store');

    Route::get('show/user/{id}', 'UserController@show')->name('show.user');
    Route::put('update/user/{id}', 'UserController@update')->name('update.user');
    Route::get('export/user', 'UserController@export')->name('export.user');
    Route::put('active/user', 'UserController@activeAccount')->name('active.user');
    Route::delete('delete/user', 'UserController@destroy')->name('delete.user');
    Route::put('/deactive/user', 'UserController@deactiveAccount')->name('deactive.user');
    Route::put('/reset/password/user/{id}', 'UserController@resetPassword')->name('reset.password');

    Route::get('instances', 'UserController@instances')->name('instances');
    Route::post('create/instance', 'UserController@createInstance')->name('create.instance');
    
    Route::get('stop/instance', 'UserController@stopInstance')->name('stop.instance');
    Route::get('start/instance', 'UserController@startInstance')->name('start.instance');

    Route::put('stop/all/instance', 'UserController@stopAllInstance')->name('stop.all.instance');
    Route::put('start/start/instance', 'UserController@startAllInstance')->name('start.all.instance');

    Route::put('renew/instance', 'UserController@newinstance')->name('renew.instance');
    Route::delete('remove/instance', 'UserController@removeInstance')->name('remove.instance');

    Route::get('get/ip', 'UserController@getIp')->name('get.ip');

    Route::get('instance/rdp', 'UserController@instanceRDP')->name('instance.rdp');

    Route::get('cron/stop/all/instance', 'ApiController@croneStopAllInstance');

    Route::post('add/bonus/time', 'UserController@addBonus')->name('add.bonus');

    Route::get('amis', 'AMIController@index')->name('index.ami');
    Route::get('create/ami', 'AMIController@create')->name('create.ami');
    Route::get('edit/ami/{id}', 'AMIController@edit')->name('edit.ami');
    Route::post('store/ami', 'AMIController@store')->name('store.ami');
    Route::put('update/ami/{id}', 'AMIController@update')->name('update.ami');
    Route::delete('delete/ami', 'AMIController@destroy')->name('destroy.ami');




    

