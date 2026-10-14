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

        $securityCheckValid = $this->verifySecurityCheck($payload);

        // A payload whose signature doesn't verify can't be trusted to report its own
        // outcome honestly - an altered-in-transit "success" is exactly the attack this
        // check exists to catch, so it's recorded as failed regardless of what the payload
        // itself claims. The raw payload (including its claimed status) is still kept intact
        // in the payload column for forensics; only the trusted status column is overridden.
        $trustedStatus = $securityCheckValid ? ($payload['status'] ?? null) : 'failed';

        if (!$securityCheckValid) {
            Log::warning('shop.thegift.lk: webhook security_check did not match - status forced to failed', ['payload' => $payload]);
        }

        WebhookLog::create([
            'uuid' => $payload['uuid'] ?? null,
            'order_reference' => $payload['order_reference'] ?? null,
            'status' => $trustedStatus,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'merchant_validation_code' => $payload['merchant_validation_code'] ?? null,
            'security_check_valid' => $securityCheckValid,
            'payload' => $payload,
        ]);

        // Always 200 even when security_check fails to verify - re-delivery won't change
        // the outcome of a deterministic signature mismatch, so there's no reason to trigger
        // thegift.lk's retry policy here. The mismatch is logged and visible per-row on
        // /webhook-logs instead.
        return response()->json(['ok' => true]);
    }

    // Mirrors the exact PHP example in merchant.thegift.lk's Developer Options documentation
    // (Verifying notify_url section): HMAC-SHA256 over uuid + order_reference + status +
    // amount (fixed 2 decimal places) + currency + merchant_validation_code, keyed with our
    // own app_token, base64-encoded, compared with hash_equals() to avoid a timing attack.
    private function verifySecurityCheck(array $payload): bool
    {
        $requiredFields = ['uuid', 'order_reference', 'status', 'amount', 'currency', 'merchant_validation_code', 'security_check'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null) {
                return false;
            }
        }

        $amount = number_format((float) $payload['amount'], 2, '.', '');

        $message = $payload['uuid']
            . $payload['order_reference']
            . $payload['status']
            . $amount
            . $payload['currency']
            . $payload['merchant_validation_code'];

        $expected = base64_encode(hash_hmac('sha256', $message, config('shop.merchant_app_token'), true));

        return hash_equals($expected, $payload['security_check']);
    }
}
