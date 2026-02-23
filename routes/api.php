<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
            'email_verified_at' => $request->user()->email_verified_at,
            'created_at' => $request->user()->created_at,
        ]);
    });

    Route::post('/logout', function (Request $request) {
        $token = $request->user()->token();
        $token->revoke();

        return response()->json(['message' => 'Successfully logged out']);
    });
});