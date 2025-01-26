<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PayzeIO\LaravelPayze\Facades\Payze;

//Route::get('/', function () {
//    return view('welcome');
//});


use App\Http\Controllers\PayzeCallbackController;

Route::post('/payment/payze/callback', [PayzeCallbackController::class, 'handleCallback'])
    ->name('payze.callback');

Payze::routes();
//Route::get('/shared/posts/{post}', function (\Illuminate\Http\Request $request, Post $post){
//
//    return "Specially made just for you 💕 ;) Post id: {$post->id}";
//
//})->name('shared.post')->middleware('signed');



//if(\Illuminate\Support\Facades\App::environment('local')){
//
//    Route::get('/links/private/{chat_id}', function (\Illuminate\Http\Request $request, $video){
//
//        if(!$request->hasValidSignature()){
//            abort(401);
//        }
//
//        return redirect(env('TELEGRAM_CHANNEL_LINK'));
//    })->name('share-link')->middleware('signed');
//
//    Route::get('/playground', function (){
//
//        return URL::temporarySignedRoute('share-link', now()->addSeconds(30), [
//            'chat_id' => 53988577584
//        ]);
//    });
//}
