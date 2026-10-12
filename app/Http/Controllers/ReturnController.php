<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function show(Request $request)
    {
        $status = $request->query('status');
        $uuid = $request->query('uuid');
        $orderReference = $request->query('order_reference');
        $validationCode = $request->query('merchant_validation_code');

        $expectedOrderReference = $request->session()->pull('shop_order_reference');
        $expectedValidationCode = $request->session()->pull('shop_merchant_validation_code');

        $validated = $expectedValidationCode !== null
            && $expectedOrderReference === $orderReference
            && $expectedValidationCode === $validationCode;

        return view('checkout.return', [
            'status' => $status,
            'uuid' => $uuid,
            'orderReference' => $orderReference,
            'validationCode' => $validationCode,
            'validated' => $validated,
        ]);
    }
}
