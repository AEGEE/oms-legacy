<?php

use Illuminate\Http\Request;
use App\Legacy\AuthIntranet;

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

Route::post('/intranet/login', function (Request $request) {
    $ldap = new AuthIntranet();

    $username = Input::get('legacy_username');
    $password = Input::get('legacy_password');
    
    if ($ldap->auth($username, $password)) {
        return response()->success($ldap->getUsername(), null, "User succesfully authorized against intranet server!");
    } else {
        return response()->failure();
    }
});
