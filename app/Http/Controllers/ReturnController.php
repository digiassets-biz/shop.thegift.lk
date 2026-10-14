<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function show(Request $request)
    {
        $uuid = $request->query('uuid');
        $orderReference = $request->query('order_reference');
        $validationCode = $request->query('merchant_validation_code');

        // get(), not pull() - the preloader means this page can legitimately load more than
        // once for the same attempt (reload while waiting, polling, etc). Single-use
        // consumption would strand a reload with a false "mismatch" warning even though
        // nothing is actually wrong. The real security check is notify_url's HMAC signature,
        // already verified server-side - this is just a secondary same-session sanity check.
        $expectedOrderReference = $request->session()->get('shop_order_reference');
        $expectedValidationCode = $request->session()->get('shop_merchant_validation_code');

        $validated = $expectedValidationCode !== null
            && $expectedOrderReference === $orderReference
            && $expectedValidationCode === $validationCode;

        // The return_url redirect carries no security_check - unlike notify_url, nothing here
        // is HMAC-signed, only merchant_validation_code (a value match, not a cryptographic
        // proof). The customer's own browser could also be made to hit this URL directly with
        // arbitrary query params. So the status shown on this page is never taken from the
        // query string - the page waits for the background notify_url webhook (which IS
        // signed and already verified server-side) to actually arrive, then shows whatever
        // that verified webhook says. See return-status() below.
        return view('checkout.return', [
            'uuid' => $uuid,
            'orderReference' => $orderReference,
            'validationCode' => $validationCode,
            'validated' => $validated,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $uuid = $request->query('uuid');
        if (!$uuid) {
            return response()->json(['found' => false]);
        }

        $log = WebhookLog::where('uuid', $uuid)->first();
        if (!$log) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'status' => $log->status,
            'security_check_valid' => $log->security_check_valid,
        ]);
    }
}
