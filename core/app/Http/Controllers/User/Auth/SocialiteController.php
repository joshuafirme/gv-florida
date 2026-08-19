<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Lib\SocialLogin;

class SocialiteController extends Controller
{

    public function socialLogin($provider)
    {
        $socialLogin = new SocialLogin($provider);
        return $socialLogin->redirectDriver();
    }


    public function callback($provider)
    {
        try {
            $socialLogin = new SocialLogin($provider);
            return $socialLogin->login();
        } catch (\Throwable $e) {
            report($e);
            $notify[] = ['error', 'Unable to complete social login. Please try again.'];
            return to_route('home')->withNotify($notify);
        }
    }
}
