<?php

namespace App\Http\Controllers;

use App\Model\Cart;
use App\Model\CartShipping;
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
        $shippingMethod = Helpers::get_business_settings('shipping_method');
        $cart_group_ids = CartManager::get_cart_group_ids();
        // return count($ cart_group_ids);
        $carts = Cart::whereIn('cart_group_id', $cart_group_ids)->get();
        // dd($carts);

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
                'gross_amount' =>round($order_amount),
                // 'description' => 'Transaction ID: ' . $tran,
            ),
            // 'item_details' => array(
            //     [
            //         'id' => 'a1',
            //         'price' => '10000',
            //         'quantity' => 1,
            //         'name' => 'Apel'
            //     ],[
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
        );
        
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        // dd($snapToken);
        return view('web-views.checkout-payment',['snap_token'=>$snapToken]);
    }

    public function callback(Request $request){
        return $request;
    }
}
