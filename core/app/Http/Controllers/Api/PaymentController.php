<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\PaymentGatewayService;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $paymentGateways)
    {
    }

    public function methods()
    {
        $gatewayCurrency = $this->paymentGateways->getEnabledGatewayCurrencies(false);
        $notify[] = 'Payment Methods';
        return response()->json([
            'remark' => 'deposit_methods',
            'message' => ['success' => $notify],
            'data' => [
                'methods' => $gatewayCurrency,
                'image_path' => getFilePath('gateway')
            ],
        ]);
    }

    public function depositInsert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'method_code' => 'required',
            'currency' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark' => 'validation_error',
                'status' => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }


        $user = auth()->user();
        try {
            $gate = $this->paymentGateways->validateGatewayCurrency(
                $request->method_code,
                $request->currency,
                false
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'remark' => 'validation_error',
                'status' => 'error',
                'message' => ['error' => $exception->errors()['gateway']],
            ]);
        }

        if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
            $notify[] = 'Please follow deposit limit';
            return response()->json([
                'remark' => 'validation_error',
                'status' => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $charge = $gate->fixed_charge + ($request->amount * $gate->percent_charge / 100);
        $payable = $request->amount + $charge;
        $finalAmount = $payable * $gate->rate;

        $data = new Deposit();
        $data->from_api = 1;
        $data->user_id = $user->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $request->amount;
        $data->charge = $charge;
        $data->rate = $gate->rate;
        $data->final_amount = $finalAmount;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        $data->success_url = urlPath('user.deposit.history');
        $data->failed_url = urlPath('user.deposit.history');
        $data->trx = getTrx();
        $data->save();

        $notify[] = 'Deposit inserted';
        return response()->json([
            'remark' => 'deposit_inserted',
            'status' => 'success',
            'message' => ['success' => $notify],
            'data' => [
                'deposit' => $data,
                'redirect_url' => route('deposit.app.confirm', encrypt($data->id))
            ]
        ]);
    }

    public function getIp()
    {
        $apiKey = env("COINS_API_KEY");
        $baseUrl = env("COINS_BASE_URL");

        $response = Http::withHeaders([
            "X-COINS-APIKEY" => $apiKey,
        ])->asForm()->get(
                $baseUrl . "/openapi/v1/user/ip"
            );

        return $response->json();
    }
}
