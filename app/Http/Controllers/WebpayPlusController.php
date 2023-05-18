<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
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
            $transaccion = new Transaccion();
            $transaccion->buy_order = $request->buy_order;
            $transaccion->sessionId = '';
            $transaccion->amount = $request->amount;
            $transaccion->returnUrl = $request->return_url;
            $transaccion->callbackUrl = $request->callback_url;
            $transaccion->anularUrl = '';
            $transaccion->token = '';
            $transaccion->save();

            $tbk = $this->createdTransaction($request->buy_order, $request->amount);
            $url = $tbk->url.'?token_ws='.$tbk->token;
            return response()->json(['data' => $url], 200);
        } catch (\Exception $e){
            return response()->json(['error' => $e], 400);
        }
    }

    public function createdTransaction2()
    {
        dd(route('returnUrl'));
    }

    public function createdTransaction($buy_order, $amount)
    {
        $session_id = rand(1000000, 9999999);
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
    
                $return_url = "";
                $session_id = '99-' . $tbk->transactionDate;
    
                $transaccion = Transaccion::find($tbk->buyOrder);
                $transaccion->sessionId = $session_id;
                $transaccion->token = $token;
                $transaccion->save();

                $return_url = $transaccion->returnUrl . '?session_id=' . $session_id;

                return redirect($return_url);
            }
            return response()->json(["resp" => $request->all()], 400);
        } catch(\Exception $e) {
            return response()->json(['error' => $e], 400);
        }
    }

    public function getTransactionStatus(Request $request)
    {
        try {
            $token = $request->input('token');
            $tbk = (new Transaction)->status($token);
            $transaccion = Transaccion::find($tbk->buyOrder);
            return response()->json(['token' => $token, 'session_id' => $transaccion->sessionId, 'comprobante' => $tbk], 200);
        } catch (\Exception $e){
            return response()->json(['error' => $e], 400);
        }
    }
}
