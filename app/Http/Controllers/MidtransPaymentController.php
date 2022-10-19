<?php

namespace App\Http\Controllers;

use App\CPU\CartManager;
use App\CPU\Helpers;
use App\CPU\OrderManager;
use App\Model\Order;
use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\Process\Exception\InvalidArgumentException;

use Illuminate\Http\Request;

class MidtransPaymentController extends Controller
{
    
    public function payment(Request $request){

        //This generates a payment reference

        $tran = Str::random(6) . '-' . rand(1, 1000);
        $order_id = Order::orderBy('id', 'DESC')->first()->id ?? 100001;
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $order_amount = CartManager::cart_grand_total() - $discount;
        $user_data = Helpers::get_customer();
        $config = Helpers::get_business_settings('midtrans');
        $client_Key = $config['Client_Key'];
        $serverKey = $config['Server_Key'];

        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = $config['Server_Key'];
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = false;
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = true;

        $params = array(
            'transaction_details' => array(
                'order_id' => $order_id,
                'gross_amount' =>$order_amount,
            ),
            // 'item_details' => array(
            //     [
            //         'id' => 'a1',
            //         'price' => '10000',
            //         'quantity' => 1,
            //         'name' => 'Apel'
            //     ],
            //     [
            //         'id' => 'b1',
            //         'price' => '8000',
            //         'quantity' => 1,
            //         'name' => 'Jeruk'
            //     ]
            // ),
            // 'customer_details' => array(
            //     'first_name' => $request->get('uname'),
            //     'last_name' => '',
            //     'email' => $request->get('email'),
            //     'phone' => $request->get('number'),
            // ),
            // 'shipping_address' => array(
            //     'first_name' => 'Budi',
            //     'last_name'=> 'Susanto',
            //     'email' => 'budisusanto@example.com',
            //     'phone' => '0812345678910',
            //     'address'=> 'Sudirman',
            //     'city'=> 'Jakarta',
            //     'postal_code' => '12190',
            //     'country_code'=>'IDN'
            // )
        );

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        // dd($snapToken);
        return view('web-views.midtrans.midtrans-payment',['snap_token'=>$snapToken]);
    }

    public function callback(Request $request){
        return $request;
    }
}
