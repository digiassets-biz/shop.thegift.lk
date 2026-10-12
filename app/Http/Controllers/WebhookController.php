<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function notify(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('shop.thegift.lk: webhook received', $payload);

        WebhookLog::create([
            'uuid' => $payload['uuid'] ?? null,
            'order_reference' => $payload['order_reference'] ?? null,
            'status' => $payload['status'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'merchant_validation_code' => $payload['merchant_validation_code'] ?? null,
            'payload' => $payload,
        ]);

        return response()->json(['ok' => true]);
    }
}
