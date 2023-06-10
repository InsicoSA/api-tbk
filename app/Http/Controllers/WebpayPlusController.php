<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use stdClass;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;
use Transbank\Webpay\WebpayPlus\Transaction;

class WebpayPlusController extends Controller
{
    public function __construct(){
        if (app()->environment('production')) {
            WebpayPlus::configureForProduction(config('services.transbank.webpay_plus_cc'), config('services.transbank.webpay_plus_api_key'));
        } else {
            WebpayPlus::configureForTesting();
        }
    }

    public function createTransaction(Request $request)
    {
        try{
            $session_id = $request->appId . '-' . date("Y-m-d\TH:i:s.v\Z");
            $transaccion = new Transaccion();
            $transaccion->buyOrder = $request->buyOrder;
            $transaccion->sessionId = $session_id;
            $transaccion->amount = $request->amount;
            $transaccion->callbackUrl = $request->callbackUrl;
            $transaccion->anularUrl = $request->anularUrl;
            $transaccion->returnUrl = '';
            $transaccion->token = '';
            $transaccion->save();

            $tbk = $this->createdTransaction($request->buyOrder, $request->amount, $session_id);
            $url = $tbk->url.'?token_ws='.$tbk->token;
            $this->updateToken($request->buyOrder, $session_id, $tbk->token);
            return response()->json(['data' => $url], 200);
        } catch (\Exception $e){
            return response()->json(['error' => $e], 400);
        }
    }

    public function updateToken($buyOrder, $session_id, $token)
    {
        $transaccion = Transaccion::where('buyOrder', $buyOrder)->where('sessionId', $session_id)->first();
        $transaccion->token = $token;
        $transaccion->save();
    }

    public function createdTransaction2()
    {
        dd(route('returnUrl'));
    }

    public function createdTransaction($buy_order, $amount, $session_id)
    {
        $transaction = (new Transaction)->create($buy_order, $session_id, $amount, route('returnUrl'));
        return $transaction;
    }

    public function commitTransaction(Request $request)
    {
        try {
            if($request->exists("token_ws")){
                $req = $request->except('_token');
                $token = $req["token_ws"];
                $tbk = (new Transaction)->commit($token);
    
                $transaccion = Transaccion::where('token', $token);
                
                $return_url = $transaccion->callbackUrl . '?session_id=' . $transaccion->sessionId;

                return redirect($return_url);
            }
            return response()->json(["resp" => $request->all()], 400);
        } catch(\Exception $e) {
            return response()->json(['error' => $e], 400);
        }
    }

    public function getTransactionComprobante(Request $request)
    {
        try {
            $session_id = $request->input('session_id');
            $transaccion = Transaccion::where('sessionId', $session_id)->first();
            $token = $transaccion->token;
            $tbk = (new Transaction)->status($token);
            $card = new stdClass;
            $card->card_number = $tbk->cardNumber;

            $pago = new stdClass;
            $pago->status = $tbk->status;
            $pago->response_code = $tbk->responseCode;
            $pago->amount = $tbk->amount;
            $pago->authorization_code = $tbk->authorizationCode;
            $pago->payment_type_code = $tbk->paymentTypeCode;
            $pago->accounting_date = $tbk->accountingDate;
            $pago->installments_number = $tbk->installmentsNumber;
            $pago->session_id = $tbk->sessionId;
            $pago->buy_order = $tbk->buyOrder;
            $pago->card_detail = $card;
            $pago->transaction_date = $tbk->transactionDate;
            return response()->json(['token' => $token, 'session_id' => $session_id, 'comprobante' => $pago], 200);
        } catch (\Exception $e){
            return response()->json(['error' => $e], 400);
        }
    }

    public function getTransactionStatus(Request $request)
    {
        try {
            $token = $request->input('token');
            $tbk = (new Transaction)->status($token);
            $transaccion = Transaccion::where('buyOrder', $tbk->buyOrder);
            return response()->json(['token' => $token, 'session_id' => $transaccion->sessionId, 'comprobante' => $tbk], 200);
        } catch (\Exception $e){
            return response()->json(['error' => $e], 400);
        }
    }
}
