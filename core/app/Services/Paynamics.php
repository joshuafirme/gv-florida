<?php
namespace App\Services;

use Storage;

class Paynamics
{
    public $user;
    public $data;
    public $pchannel;
    public function __construct($user)
    {
        $this->user = $user;
    }
    public function createTransaction()
    {
        try {
            $pmethod = getPaynamicsPMethod($this->pchannel);

            $orders = [];
            $orders[] = [
                "itemname" => "PNR: {$this->data->pnr_number} Seats: " . implode(', ', $this->data->seats),
                "quantity" => 1,
                "unitprice" => $this->data->deposit->final_amount,
                "totalprice" => $this->data->deposit->final_amount
            ];

            $base_url = config('app.url');
            $merchantid = config('paynamics.merchant_id');
            $mkey = config('paynamics.merchant_key');
            $basicUser = config('paynamics.basic_auth_user');
            $basicPass = config('paynamics.basic_auth_pw');

            $data = [
                "transaction" => [
                    "request_id" => $this->data->deposit->trx,
                    "notification_url" => "{$base_url}api/paynamics/notification",
                    "response_url" => "{$base_url}user/paynamics/response",
                    "cancel_url" => "{$base_url}user/paynamics/cancel",
                    "pmethod" => $pmethod,
                    "pchannel" => $this->pchannel,
                    "payment_action" => "url_link",
                    "collection_method" => "single_pay",
                    "payment_notification_status" => "1",
                    "payment_notification_channel" => "1",
                    "amount" => $this->data->deposit->final_amount,
                    "currency" => "PHP",
                    "trx_type" => "sale",
                    // "mtac_url" => ""
                ],
                "customer_info" => [
                    "fname" => $this->user->firstname,
                    "lname" => $this->user->lastname,
                    "mname" => "",
                    "email" => $this->user->email,
                    "phone" => $this->user->mobile,
                    "mobile" => $this->user->mobile,
                    "dob" => ""
                ],
                "order_details" => [
                    "orders" => $orders,
                    "subtotalprice" => $this->data->deposit->final_amount,
                    "shippingprice" => "0.00",
                    "discountamount" => "0.00",
                    "totalorderamount" => $this->data->deposit->final_amount
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
            $path = "paynamics/payloads/{$this->data->deposit->trx}.json";
            Storage::put($path, $jsonPayload);

            $api_base = config('paynamics.endpoint');

            // cURL Request to Paynamics
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "{$api_base}transactions");
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

                return $json_res;
            }
   } catch (\Exception $e) {
            $response = "Message: " . $e->getMessage() . "<br>" .
                "File: " . $e->getFile() . "<br>" .
                "Line: " . $e->getLine() . "<br>";

            return response()->json($response);
        }
    }

    public function queryTransaction()
    {
        $merchantid = config('paynamics.merchant_id');
        $mkey = config('paynamics.merchant_key');
        $basicUser = config('paynamics.basic_auth_user');
        $basicPass = config('paynamics.basic_auth_pw');

        $org_trxid2 = session('paynamics_request_id') ? session('paynamics_request_id') : '';
        $date = date('Ymd');
        $req_id = generateReqID();
        $rawTrx = $merchantid . $req_id . $org_trxid2 . $mkey;

        $signatureTrx = hash('sha512', $rawTrx);
        $data = [
            "request_id" => $req_id,
            "org_trxid2" => $org_trxid2,
            "signature" => $signatureTrx
        ];

        $jsonPayload = json_encode($data);

        $api_base = config('paynamics.endpoint');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$api_base}transactions/query");
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
            return $json_res;
        }
    }
}