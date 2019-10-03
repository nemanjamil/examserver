<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix' => '1.0'], function () {
    Route::get('getconnection', "SqmsExamVersionController@getconnection");
    Route::get('all', "SqmsExamVersionController@index");
    Route::get('hashsalt', "SqmsExamVersionController@hashsalt");
    Route::post('sentdata',"SqmsExamVersionController@show");
    Route::get('azure', "AzureController@index");
});

