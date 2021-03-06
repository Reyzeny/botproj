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

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', 'BotManController@tinker');

Route::match(['get', 'post'], '/backend', 'BotManController@handle');
Route::get('/botman/tinker', 'BotManController@tinker');
Route::match(['get', 'post'], '/confirm_complete_payment', 'BotManController@confirm_complete_payment');
