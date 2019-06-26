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

Route::group(array('middleware' => 'api'), function () {
    Route::get('/calendars/{email}', array(
        'uses' => '\App\Http\Controllers\MicrosoftGraphController@getCalendars',
        'as'   => 'microsoft-graph.calendars.get',
    ));
    Route::get('/eventsAll/{email}', array(
        'uses' => '\App\Http\Controllers\MicrosoftGraphController@getEventsAll',
        'as'   => 'microsoft-graph.eventsAll.get',
    ));
    Route::get('/events/{email}', array(
        'uses' => '\App\Http\Controllers\MicrosoftGraphController@getEvents',
        'as'   => 'microsoft-graph.events.get',
    ));
    Route::post('/events/{email}', array(
        'uses' => '\App\Http\Controllers\MicrosoftGraphController@createEvents',
        'as'   => 'microsoft-graph.events.create',
    ));
    Route::patch('/events/{email}', array(
        'uses' => '\App\Http\Controllers\MicrosoftGraphController@updateEvents',
        'as'   => 'microsoft-graph.events.update',
    ));
    Route::delete('/events/{email}', array(
        'uses' => '\App\Http\Controllers\MicrosoftGraphController@deleteEvents',
        'as'   => 'microsoft-graph.events.delete',
    ));
});