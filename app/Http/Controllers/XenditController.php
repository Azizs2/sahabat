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

use Xendit\Xendit;

class XenditController extends Controller
{
    function __construct() {
        $config = Helpers::get_business_settings('xendit');
        $secret_key = $config['SECRET_API_KEY'];
        $api_key = $config['API_KEY'];
        $api_url_key = $config['API_GATEWAY_URL'];
        Xendit::setApiKey($api_key);
    }

    public function payment()
    {
        $tran = Str::random(6) . '-' . rand(1, 1000);
        $order_id = Order::orderBy('id', 'DESC')->first()->id ?? 100001;
        // dd($order_id);
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $value = CartManager::cart_grand_total() - $discount;

        $params = [ 
            'external_id' => 'tanlao_'.$order_id,
            'amount' => $value,
            'description' => 'Transaction: ' . $tran,
            'invoice_duration' => 7000,
            // 'customer' => [
            //     'given_names' => 'John',
            //     'surname' => 'Doe',
            //     'email' => 'johndoe@example.com',
            //     'mobile_number' => '+6287774441111',
            //     'addresses' => [
            //         [
            //             'city' => 'Jakarta Selatan',
            //             'country' => 'Indonesia',
            //             'postal_code' => '12345',
            //             'state' => 'Daerah Khusus Ibukota Jakarta',
            //             'street_line1' => 'Jalan Makan',
            //             'street_line2' => 'Kecamatan Kebayoran Baru'
            //         ]
            //     ]
            // ],
            // 'customer_notification_preference' => [
            //     'invoice_created' => [
            //         'whatsapp',
            //         'sms',
            //         'email'
            //     ],
            //     'invoice_reminder' => [
            //         'whatsapp',
            //         'sms',
            //         'email'
            //     ],
            //     'invoice_paid' => [
            //         'whatsapp',
            //         'sms',
            //         'email'
            //     ],
            //     'invoice_expired' => [
            //         'whatsapp',
            //         'sms',
            //         'email'
            //     ]
            // ],
            'success_redirect_url' => route('xendit-callback'),
            'failure_redirect_url' => route('xendit-callback'),
            'currency' => Helpers::currency_code(),
            // 'items' => [
            //     [
            //         'name' => 'Air Conditioner',
            //         'quantity' => 1,
            //         'price' => 100000,
            //         'category' => 'Electronic',
            //         'url' => 'https=>//yourcompany.com/example_item'
            //     ]
            // ],
            // 'fees' => [
            //     [
            //         'type' => 'ADMIN',
            //         'value' => 5000
            //     ]
            // ]
          ];

        $createInvoice = \Xendit\Invoice::create($params);
        $url = $createInvoice['invoice_url'];
        return $createInvoice;
        // return redirect()->to($url);
        // return view('web-views.xendit.xendit',compact('url'));
    }

    public function callback(Request $request)
    {
        dd($request);
        $request['external_id'] = session('external_id');
        if ($request['status'] == 'PAID') {
            $unique_id = OrderManager::gen_unique_id();
            $order_ids = [];
            foreach (CartManager::get_cart_group_ids() as $group_id) {
                $data = [
                    'payment_method' => 'xendit',
                    'order_status' => 'confirmed',
                    'payment_status' => 'PAID',
                    'transaction_ref' => 'trx_' . $unique_id,
                    'order_group_id' => $unique_id,
                    'cart_group_id' => $group_id
                ];
                $order_id = OrderManager::generate_order($data);
                array_push($order_ids, $order_id);
            }

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                CartManager::cart_clean();
                return redirect()->route('payment-success');
            } else {
                CartManager::cart_clean();
                return view('web-views.checkout-complete');
            }
        }

        if (session()->has('payment_mode') && session('payment_mode') == 'app') {
            return redirect()->route('payment-fail');
        }
        Toastr::error('Payment process failed!');
        return back();
    }
}
