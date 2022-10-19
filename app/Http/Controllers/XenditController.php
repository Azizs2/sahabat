<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\CPU\CartManager;
use App\CPU\Helpers;
use App\CPU\OrderManager;
use App\Model\Order;
use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class XenditController extends Controller
{
    public function payment()
    {
        $tran = Str::random(6) . '-' . rand(1, 1000);
        $order_id = Order::orderBy('id', 'DESC')->first()->id ?? 100001;
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $value = CartManager::cart_grand_total() - $discount;
        $config = Helpers::get_business_settings('xendit');
        dd($config);

        // $public_key = $config['public_key'];
        // $private_key = $config['private_key'];
        // $liqpay = new LiqPay($public_key, $private_key);
        // $html = $liqpay->cnb_form(array(
        //     'action' => 'pay',
        //     'amount' => round($value, 2),
        //     'currency' => Helpers::currency_code(), //USD
        //     'description' => 'Transaction ID: ' . $tran,
        //     'order_id' => $order_id,
        //     'result_url' => route('liqpay-callback'),
        //     'server_url' => route('liqpay-callback'),
        //     'version' => '3'
        // ));
        // return $html;
    }

}
