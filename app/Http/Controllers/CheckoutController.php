<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $slug = $request->query('product');
        $qty = max(1, min(5, (int) $request->query('qty', 1)));

        $product = config("products.$slug");
        abort_if(!$product, 404);

        $amount = $product['price'] * $qty;

        return view('checkout.show', [
            'slug' => $slug,
            'product' => $product,
            'qty' => $qty,
            'amount' => $amount,
            'customerPhone' => config('shop.customer_phone'),
            'customerEmail' => config('shop.customer_email'),
            'error' => $request->session()->get('checkout_error'),
        ]);
    }

    public function pay(Request $request)
    {
        $slug = $request->input('product');
        $qty = max(1, min(5, (int) $request->input('qty', 1)));

        $product = config("products.$slug");
        abort_if(!$product, 404);

        $amount = $product['price'] * $qty;
        $orderReference = 'SHOP-' . now()->timestamp;

        // api.thegift.lk checks this against the app_domain registered for our app_token -
        // without it (or with a mismatched value), /merchant/pay rejects the request with
        // status 100 even if the token itself is valid.
        $response = Http::timeout(15)
            ->withHeaders(['Origin' => rtrim(config('app.url'), '/')])
            ->post(rtrim(config('shop.api_base_url'), '/') . '/merchant/pay', [
                'merchant_id' => config('shop.merchant_id'),
                'merchant_app_token' => config('shop.merchant_app_token'),
                'currency' => 'LKR',
                'amount' => $amount,
                'order_reference' => $orderReference,
                'customer_phone_number' => config('shop.customer_phone'),
                'customer_email' => config('shop.customer_email'),
                'notify_url' => route('webhook.notify'),
                'return_url' => route('checkout.return'),
                'custom_param_1' => config('shop.custom_param_1'),
                'custom_param_2' => config('shop.custom_param_2'),
            ]);

        $data = $response->json();

        if (!$response->successful() || ($data['status'] ?? null) !== 200) {
            Log::warning('shop.thegift.lk: /merchant/pay failed', ['response' => $data]);

            return redirect()
                ->route('checkout.show', ['product' => $slug, 'qty' => $qty])
                ->with('checkout_error', $data['message'] ?? 'Payment initiation failed. Please try again.');
        }

        $request->session()->put('shop_order_reference', $orderReference);
        $request->session()->put('shop_merchant_validation_code', $data['merchant_validation_code']);

        return redirect()->away($data['payment_link']);
    }
}
