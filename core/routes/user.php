<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::
        namespace('User\Auth')->middleware('guest')->name('user.')->group(function () {
            Route::controller('LoginController')->group(function () {
                Route::get('/login', 'showLoginForm')->name('login');
                Route::post('/login', 'login');
                Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
            });

            Route::controller('RegisterController')->middleware(['guest'])->group(function () {
                Route::get('register', 'showRegistrationForm')->name('register');
                Route::post('register', 'register');
                Route::post('check-user', 'checkUser')->name('checkUser')->withoutMiddleware('guest');
            });

            Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
                Route::get('reset', 'showLinkRequestForm')->name('request');
                Route::post('email', 'sendResetCodeEmail')->name('email');
                Route::get('code-verify', 'codeVerify')->name('code.verify');
                Route::post('verify-code', 'verifyCode')->name('verify.code');
            });

            Route::controller('ResetPasswordController')->group(function () {
                Route::post('password/reset', 'reset')->name('password.update');
                Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
            });

            Route::controller('SocialiteController')->group(function () {
                Route::get('social-login/{provider}', 'socialLogin')->name('social.login');
                Route::get('social-login/callback/{provider}', 'callback')->name('social.login.callback');
            });
        });

Route::middleware('auth')->name('user.')->group(function () {

    Route::get('user-data', 'User\UserController@userData')->name('data');
    Route::post('user-data-submit', 'User\UserController@userDataSubmit')->name('data.submit');
    Route::post('paynamics/redirect', 'Gateway\Paynamics\ProcessController@redirect')->name('paynamics.redirect');
    Route::get('paynamics/response', 'Gateway\Paynamics\ProcessController@response')->name('paynamics.response');
    Route::post('paynamics/notification', 'Gateway\Paynamics\ProcessController@notification')->name('paynamics.notification');

    //authorization
    Route::middleware('registration.complete')->namespace('User')->controller('AuthorizationController')->group(function () {
        Route::get('authorization', 'authorizeForm')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'emailVerification')->name('verify.email');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
        Route::post('verify-g2fa', 'g2faVerification')->name('2fa.verify');
    });

    Route::middleware(['check.status', 'registration.complete'])->group(function () {

        Route::namespace('User')->group(function () {

            Route::controller('UserController')->group(function () {
                Route::get('dashboard', 'home')->name('home');
                Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');

                //2FA
                Route::get('twofactor', 'show2faForm')->name('twofactor');
                Route::post('twofactor/enable', 'create2fa')->name('twofactor.enable');
                Route::post('twofactor/disable', 'disable2fa')->name('twofactor.disable');

                //ticket
                Route::get('booked-ticket/history', 'ticketHistory')->name('ticket.history');

                //Report
                Route::any('payment/history', 'depositHistory')->name('deposit.history');
                Route::get('transactions', 'transactions')->name('transactions');

                Route::post('add-device-token', 'addDeviceToken')->name('add.device.token');
            });

            //Profile setting
            Route::controller('ProfileController')->group(function () {
                Route::get('profile-setting', 'profile')->name('profile.setting');
                Route::post('profile-setting', 'submitProfile');
                Route::get('change-password', 'changePassword')->name('change.password');
                Route::post('change-password', 'submitPassword');
            });
        });

        // Payment
        Route::prefix('payment')->controller('Gateway\PaymentController')->group(function () {
            // Route::any('/', 'deposit')->name('index');
            // Route::post('insert', 'depositInsert')->name('insert');
            // Route::get('confirm', 'depositConfirm')->name('confirm');
            // Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
            // Route::post('manual', 'manualDepositUpdate')->name('manual.update');
        });
    });
});

Route::get('payment/deposit', 'Gateway\PaymentController@deposit')->name('user.deposit.index');
Route::post('payment/insert', 'Gateway\PaymentController@depositInsert')->name('user.deposit.insert');
Route::get('payment/confirm', 'Gateway\PaymentController@depositConfirm')->name('user.deposit.confirm');

Route::get('payment/manual', 'Gateway\PaymentController@manualDepositConfirm')->name('user.deposit.manual.confirm');
Route::post('payment/manual', 'Gateway\PaymentController@manualDepositUpdate')->name('user.deposit.manual.update');

Route::get('booked-ticket/print/{id}', 'User\UserController@printTicket')->name('user.ticket.print');