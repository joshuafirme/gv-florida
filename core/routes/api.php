<?php

use Illuminate\Support\Facades\Route;

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
use Coreproc\PaynamicsSdk\Requests\PaymentRequest;
use Coreproc\PaynamicsSdk\Requests\ItemRequest;

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

Route::
        namespace('Api')->name('api.')->group(function () {

            Route::get('paynamics', function () {
                // $merchant_id = "000000060825R2JDJ4XD";
                // $request_id = uniqid("txn_");  // unique transaction ID
                // $amount = "500.00";        // example amount
                // $currency = "PHP";
        
                // $data = array(
                //     "mid" => $merchant_id,
                //     "request_id" => $request_id,
                //     "amount" => $amount,
                //     "currency" => $currency,
                //     "response_url" => env('APP_URL') . "paynamics-response",
                //     "cancel_url" => env('APP_URL') . "payment-cancel",
                //     "notification_url" => env('APP_URL') . "paynamics-notify",
                //     "payment_method" => "card",
                //     "mtac_url" => "terms",
                //     "description" => "Bus Ticket Booking"
                // );
        
                // // Convert to JSON and encrypt/sign as required by Paynamics
                // $json_data = json_encode($data);
        
                // // Usually, Paynamics requires you to sign the data with a secret key
                // $signature = base64_encode(hash_hmac('sha256', $json_data, 'TM3RKZ8T7PUP6D5DFZCI1NR5ZCMZIE27', true));
        
                // // Send to Paynamics API (example)
                // $ch = curl_init('https://payin.payserv.net/paygate/transactions/');
                // curl_setopt($ch, CURLOPT_POST, true);
                // curl_setopt($ch, CURLOPT_POSTFIELDS, array('data' => $json_data, 'signature' => $signature));
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // $response = curl_exec($ch);
                // dd($response);
                // curl_close($ch);
        
                // return response()->json($response);
                
                $itemRequest = ItemRequest::make()
                    ->setItemName('ticket')
                    ->setAmount(300)
                    ->setQuantity(1);


                $paymentRequest = PaymentRequest::make()
                    ->setIpAddress('82.180.152.171')
                    ->setNotificationUrl(env('APP_URL') . "paynamics-notify")
                    ->setResponseUrl(env('APP_URL') . "paynamics-response")
                    ->setCancelUrl(env('APP_URL') . "payment-cancel")
                    ->setFname('John')
                    ->setLname('Doe')
                    ->setAddress1('test address')
                    ->setCity('Nasugbu')
                    ->setState('Batangas')
                    ->setEmail('artisan.dev1699@gmail.com')
                    ->setMobile('09902920292')
                    ->setClientIp(get_client_ip())
                    ->setAmount(300)
                    ->setCurrency('PHP')
                    ->setTrxtype('sale')
                    ->setPmethod('gcash')
                    ->setCountry('PH')
                    ->addItem($itemRequest);
              //  dd($paymentRequest);
                    return response()->json($paymentRequest);
            });


            Route::controller('AppController')->group(function () {
                Route::get('general-setting', 'generalSetting');
                Route::get('get-countries', 'getCountries');
                Route::get('language/{key}', 'getLanguage');
                Route::get('policies', 'policies');
                Route::get('faq', 'faq');
            });

            Route::namespace('Auth')->group(function () {
                Route::controller('LoginController')->group(function () {
                    Route::post('login', 'login');
                    Route::post('check-token', 'checkToken');
                    Route::post('social-login', 'socialLogin');
                });
                Route::post('register', 'RegisterController@register');

                Route::controller('ForgotPasswordController')->group(function () {
                    Route::post('password/email', 'sendResetCodeEmail');
                    Route::post('password/verify-code', 'verifyCode');
                    Route::post('password/reset', 'reset');
                });
            });

            Route::middleware('auth:sanctum')->group(function () {

                Route::post('user-data-submit', 'UserController@userDataSubmit');

                //authorization
                Route::middleware('registration.complete')->controller('AuthorizationController')->group(function () {
                    Route::get('authorization', 'authorization');
                    Route::get('resend-verify/{type}', 'sendVerifyCode');
                    Route::post('verify-email', 'emailVerification');
                    Route::post('verify-mobile', 'mobileVerification');
                    Route::post('verify-g2fa', 'g2faVerification');
                });

                Route::middleware(['check.status'])->group(function () {

                    Route::middleware('registration.complete')->group(function () {
                        Route::get('dashboard', function () {
                            return auth()->user();
                        });


                        Route::controller('UserController')->group(function () {

                            Route::post('profile-setting', 'submitProfile');
                            Route::post('change-password', 'submitPassword');

                            Route::get('user-info', 'userInfo');

                            //Report
                            Route::any('deposit/history', 'depositHistory');
                            Route::get('transactions', 'transactions');

                            Route::post('add-device-token', 'addDeviceToken');
                            Route::get('push-notifications', 'pushNotifications');
                            Route::post('push-notifications/read/{id}', 'pushNotificationsRead');

                            //2FA
                            Route::get('twofactor', 'show2faForm');
                            Route::post('twofactor/enable', 'create2fa');
                            Route::post('twofactor/disable', 'disable2fa');

                            Route::post('delete-account', 'deleteAccount');

                        });



                        // Payment
                        Route::controller('PaymentController')->group(function () {
                            Route::get('deposit/methods', 'methods');
                            Route::post('deposit/insert', 'depositInsert');
                        });

                        Route::controller('TicketController')->prefix('ticket')->group(function () {
                            Route::get('/', 'supportTicket');
                            Route::post('create', 'storeSupportTicket');
                            Route::get('view/{ticket}', 'viewTicket');
                            Route::post('reply/{id}', 'replyTicket');
                            Route::post('close/{id}', 'closeTicket');
                            Route::get('download/{attachment_id}', 'ticketDownload');
                        });

                    });
                });

                Route::get('logout', 'Auth\LoginController@logout');
            });
        });
