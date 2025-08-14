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

function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

Route::
        namespace('Api')->name('api.')->group(function () {

            Route::get('paynamics', function () {

                $date = date('Ymd');

                $merchantid = config('paynamics.merchant_id');
                $mkey = config('paynamics.merchant_key');
                $basicUser = config('paynamics.basic_auth_user');
                $basicPass = config('paynamics.basic_auth_pw');

                $req_id = "GVF-$date-" . substr(uniqid(), 0, 7);

                $data = [
                    "transaction" => [
                        "request_id" => $req_id,
                        "notification_url" => "https://webhook.site/78a425db-2fb1-42e2-9ea5-e108c75dbfb6",
                        "response_url" => "https://payin.payserv.net/paygate",
                        "cancel_url" => "https://payin.payserv.net/datavault",
                        "pmethod" => "onlinebanktransfer",
                        "pchannel" => "bpi_online",
                        "payment_action" => "url_link",
                        "collection_method" => "single_pay",
                        "payment_notification_status" => "1",
                        "payment_notification_channel" => "1",
                        "amount" => "1.00",
                        "currency" => "PHP",
                        "trx_type" => "sale",
                    ],
                    "billing_info" => [
                        "billing_address1" => "Unit 1108 Cityland 10 Tower 2",
                        "billing_address2" => "H.V. dela Costa Street 1227, Salcedo",
                        "billing_city" => "Makati",
                        "billing_state" => "Metro Manila",
                        "billing_country" => "Philippines",
                        "billing_zip" => "1209"
                    ],
                    "shipping_info" => [
                        "shipping_address1" => "Unit 1108 Cityland 10 Tower 2",
                        "shipping_address2" => "H.V. dela Costa Street 1227, Salcedo",
                        "shipping_city" => "Makati",
                        "shipping_state" => "Metro Manila",
                        "shipping_country" => "Philippines",
                        "shipping_zip" => "1209"
                    ],
                    "customer_info" => [
                        "fname" => "Juan",
                        "lname" => "Dela Cruz",
                        "mname" => "Santos",
                        "email" => "juan.delacruz@paynamics.net",
                        "phone" => "09123456789",
                        "mobile" => "",
                        "dob" => ""
                    ],
                    "order_details" => [
                        "orders" => [
                            [
                                "itemname" => "WEB TEST 01",
                                "quantity" => 1,
                                "unitprice" => "1.00",
                                "totalprice" => "1.00"
                            ]
                        ],
                        "subtotalprice" => "1.00",
                        "shippingprice" => "0.00",
                        "discountamount" => "0.00",
                        "totalorderamount" => "1.00"
                    ]
                ];

                // Generate Transaction Signature
                $rawTrx = $merchantid .
                    ($data["transaction"]["request_id"] ?? '') .
                    ($data["transaction"]["notification_url"] ?? '') .
                    ($data["transaction"]["response_url"] ?? '') .
                    ($data["transaction"]["cancel_url"] ?? '') .
                    ($data["transaction"]["pmethod"] ?? '') .
                    ($data["transaction"]["payment_action"] ?? '') .
                    ($data["transaction"]["schedule"] ?? '') .
                    ($data["transaction"]["collection_method"] ?? '') .
                    ($data["transaction"]["deferred_period"] ?? '') .
                    ($data["transaction"]["deferred_time"] ?? '') .
                    ($data["transaction"]["dp_balance_info"] ?? '') .
                    ($data["transaction"]["amount"] ?? '') .
                    ($data["transaction"]["currency"] ?? '') .
                    ($data["transaction"]["descriptor_note"] ?? '') .
                    ($data["transaction"]["payment_notification_status"] ?? '') .
                    ($data["transaction"]["payment_notification_channel"] ?? '') .
                    $mkey;

                $signatureTrx = hash('sha512', $rawTrx);
                $data["transaction"]["signature"] = $signatureTrx;

                // Generate Customer Signature
                $rawCustomer = ($data["customer_info"]["fname"] ?? '') .
                    ($data["customer_info"]["lname"] ?? '') .
                    ($data["customer_info"]["mname"] ?? '') .
                    ($data["customer_info"]["email"] ?? '') .
                    ($data["customer_info"]["phone"] ?? '') .
                    ($data["customer_info"]["mobile"] ?? '') .
                    ($data["customer_info"]["dob"] ?? '') .
                    $mkey;

                $signatureCustomer = hash('sha512', $rawCustomer);
                $data["customer_info"]["signature"] = $signatureCustomer;

                // Convert to JSON
                $jsonPayload = json_encode($data);

                // cURL Request to Paynamics
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://payin.payserv.net/paygate/transactions/");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json",
                    "Authorization: Basic " . base64_encode("$basicUser:$basicPass")
                ]);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo "cURL Error: " . curl_error($ch);
                } else {
                    curl_close($ch);
                    $json_res = json_decode($response);
                    if (isset($json_res->payment_action_info)) {
                        return redirect()->to($json_res->payment_action_info);
                    }
                    return $json_res;
                }
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
