# shop.thegift.lk — Pay by Plugin Test Storefront Design

**Goal:** Build a minimal Laravel storefront that integrates with TheGift's "Pay by Plugin" API as a real third-party merchant would, so the developer can personally experience a real successful payment and a real failed payment end to end, and reuse this app for the same walkthrough in every other environment (staging, UAT, production sandbox, etc).

**Non-goal:** This is not a production storefront. No auth, no cart, no real product catalog, no persistence beyond a webhook log. It exists purely to exercise the Pay by Plugin integration surface (initiation, redemption, webhook, return redirect) from the outside, the way a real merchant's server would.

## 1. Context: the Pay by Plugin contract being integrated against

This app is a *caller* of an already-built, already-shipped feature spanning four other repos (`api.thegift.lk`, `thegift.lk`, `merchant.thegift.lk`, `admin.thegift.lk`). shop.thegift.lk does not modify any of them — it only calls their public HTTP surface. The contract below was confirmed by reading the real controllers/services on 2026-09-29, and mirrors merchant.thegift.lk's own "Documentation" page for integrators word for word.

### 1.1 Initiating a payment

`POST {api_base_url}/merchant/pay` (`api.thegift.lk`, no auth header — the token below is the auth):

```json
{
  "merchant_id": "<merchant uuid>",
  "merchant_app_token": "<approved app_token>",
  "currency": "LKR",
  "amount": 1200,
  "order_reference": "SHOP-1730000000",
  "customer_phone_number": "0771234567",
  "customer_email": "customer@example.com",
  "notify_url": "https://shop.thegift.lk/notify",
  "return_url": "https://shop.thegift.lk/return",
  "custom_param_1": "shop-test",
  "custom_param_2": "v1"
}
```

All fields required except `custom_param_1`/`custom_param_2`. `currency` must equal `LKR` exactly. `amount` must be numeric and `> 0`.

**Idempotency:** the same `merchant_id` + `order_reference` pair, while an open (non-expired) link exists, returns that *same* link instead of creating a new one. shop.thegift.lk generates a fresh `order_reference` per attempt (`SHOP-<unix timestamp>`) specifically to avoid colliding with this on repeat tests.

Success response (`200`):
```json
{
  "status": 200,
  "uuid": "9d1e2c3a-...",
  "payment_link": "https://thegift.lk/pay-by-plugin/<uuid>",
  "merchant_validation_code": "ABCDEF0123456789..."
}
```

Validation failure (`422`): `{"status": 103, "message": "Not found active app for this merchant"}` (status codes 101-110, one per field — see merchant.thegift.lk's Documentation page for the full table if a request ever needs debugging).

### 1.2 The redemption page (thegift.lk, not built by this app)

The merchant's server redirects the customer's browser to `payment_link`. The customer pays there using a TheGift voucher — this is thegift.lk's hosted page, entirely outside shop.thegift.lk's control.

**Sandbox test vouchers** (only work when `thegift.lk`'s `.env` has `CUSTOM_SANDBOX_KEY` set to the value `SandboxService` expects, and `php artisan sandbox:seed-test-vouchers` has been run there — this is a prerequisite of the *thegift.lk* environment, not something shop.thegift.lk configures):

| Voucher code | Face value | Effect |
|---|---|---|
| `TGVC-SANDBOX-0500` | 500 | succeeds if order amount ≤ 500 |
| `TGVC-SANDBOX-1000` | 1,000 | succeeds if order amount ≤ 1,000 |
| `TGVC-SANDBOX-2500` | 2,500 | succeeds if order amount ≤ 2,500 |
| `TGVC-SANDBOX-5000` | 5,000 | succeeds if order amount ≤ 5,000 |
| `TGVC-SANDBOX-10000` | 10,000 | succeeds if order amount ≤ 10,000 |
| `TGVC-SANDBOX-3000` | 3,000 | **deliberately triggers the FAILED path** when order amount > 3,000 |

All six use PIN `000000`.

**To experience success:** buy Samsung S26 Ultra (1,200) or iPhone 17 Pro Plus (1,450) at qty 1, redeem with `TGVC-SANDBOX-2500` / `000000`.

**To experience failure:** raise the quantity so the total exceeds LKR 3,000 (e.g. 3× iPhone 17 Pro Plus = 4,350), redeem with `TGVC-SANDBOX-3000` / `000000` — this hits the code path that immediately expires the link and fires the webhook/return with `status=failed`, no waiting for a timeout.

### 1.3 Webhook (`notify_url`)

thegift.lk calls this **server-to-server**, `POST`, JSON body, on every terminal outcome (success, sandbox-triggered failure, or natural expiry), at most once (guarded by `is_triggered_notify`), with retries (3 attempts, 10s/30s/90s backoff) if the endpoint doesn't respond 2xx:

```json
{
  "uuid": "...",
  "order_reference": "SHOP-1730000000",
  "status": "success",
  "amount": 1200,
  "currency": "LKR",
  "merchant_validation_code": "ABCDEF0123456789...",
  "custom_param_1": "shop-test",
  "custom_param_2": "v1"
}
```
`status` is `"success"` or `"failed"`. **No signature/HMAC exists on this webhook** — `merchant_validation_code` matching what was issued at initiation is the only available anti-tamper check.

### 1.4 Return redirect (`return_url`)

After a ~2 second confirmation screen on thegift.lk, the customer's browser is redirected (real `window.location.href`, not silent) to:
```
{return_url}?status=success|failed&uuid=...&order_reference=...&merchant_validation_code=...
```
Note: no `amount`/`currency` on this redirect (unlike the webhook payload).

## 2. Architecture

**Stack:** Laravel 12, PHP 8.2 — matching every sibling app (thegift.lk, merchant.thegift.lk, admin.thegift.lk, api.thegift.lk all pin `laravel/framework: ^12.0`, `php: ^8.2`).

**No Inertia, no React, no Vite build pipeline.** Plain Blade views, one hand-written CSS file served directly from `public/css/app.css` via a `<link>` tag. This is a deliberate simplification given the throwaway/reusable-test-harness nature of the app: zero `npm install`/`npm run build` step needed to run it in any environment, ever. The default Laravel installer's Vite/`resources/css`/`resources/js`/`package.json` scaffolding is removed.

**No database beyond one table** (`webhook_logs`) purely to give the developer a way to visually confirm the webhook fired with the right payload across repeated test runs and environments. No products table, no orders table, no auth, no users table beyond Laravel's default (unused).

## 3. Components

### 3.1 Products (hardcoded, no DB)

A single PHP array/config (`config/products.php`), keyed by slug:
```php
return [
    'samsung-s26-ultra' => ['name' => 'Samsung S26 Ultra', 'price' => 1200],
    'iphone-17-pro-plus' => ['name' => 'iPhone 17 Pro Plus', 'price' => 1450],
];
```

### 3.2 Routes

| Method | Path | Controller action | Purpose |
|---|---|---|---|
| GET | `/` | `ProductController@index` | List the 2 products, each with a qty stepper (1-5, default 1) and "Buy Now" |
| GET | `/checkout` | `CheckoutController@show` | Reads `product` + `qty` query params, shows amount, hardcoded billing info, fixed "TheGift Voucher" payment method, "Pay Now" |
| POST | `/checkout/pay` | `CheckoutController@pay` | Calls `/merchant/pay`, stores `order_reference` + `merchant_validation_code` in session, redirects to `payment_link` on success or back to checkout with an error on failure |
| POST | `/notify` | `WebhookController@notify` | Logs + persists the webhook payload, CSRF-exempt, returns `200` |
| GET | `/return` | `ReturnController@show` | Reads query params, compares `merchant_validation_code` against the session value, renders success/failed confirmation |
| GET | `/webhook-logs` | `WebhookLogController@index` | Lists all received webhook payloads, newest first |

### 3.3 `webhook_logs` table

One migration:
```php
Schema::create('webhook_logs', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->nullable();
    $table->string('order_reference')->nullable();
    $table->string('status')->nullable();
    $table->decimal('amount', 12, 2)->nullable();
    $table->string('currency')->nullable();
    $table->string('merchant_validation_code')->nullable();
    $table->json('payload');
    $table->timestamps();
});
```
`payload` stores the raw decoded JSON body as received, so nothing is ever lost even if a future field gets added upstream.

### 3.4 Config / `.env`

```
SHOP_API_BASE_URL=http://api.thegift.lk
SHOP_MERCHANT_ID=
SHOP_MERCHANT_APP_TOKEN=
SHOP_CUSTOMER_PHONE=0771234567
SHOP_CUSTOMER_EMAIL=customer@example.com
SHOP_CUSTOM_PARAM_1=shop-test
SHOP_CUSTOM_PARAM_2=v1
```
Mapped into `config/shop.php`. `notify_url`/`return_url` are NOT stored separately — they're built at request time from `config('app.url')` (`/notify`, `/return`), so they always match wherever this app is actually running, in any environment.

`SHOP_MERCHANT_ID`/`SHOP_MERCHANT_APP_TOKEN` are left blank in `.env.example`; the developer fills in real values per environment (as stated in the original request).

## 4. Error handling

- `/merchant/pay` returns non-200: `CheckoutController@pay` redirects back to `/checkout` with the API's `message` flashed as an error, no payment link followed.
- `/notify` always returns `200` once the payload is persisted (even for an unrecognized/malformed body — logged as best-effort, since thegift.lk will retry 3x on non-2xx and there's no reason to trigger that for a test harness).
- `/return` with a missing/mismatched `merchant_validation_code` (e.g. session expired, or someone hand-edits the URL) shows the payment status from the query string but flags the validation mismatch visibly rather than silently trusting it — mirrors what a careful real integration would do.

## 5. Verification plan

No PHPUnit — this app follows the same convention as every sibling app in this workspace: `php -l` on every new file, and manual browser walkthroughs. Concretely, after Phase 2 is built:

1. Register/confirm an approved app token for a merchant on merchant.thegift.lk's Developer Options → Integrations page (prerequisite, done by the user outside this app).
2. Set `SHOP_MERCHANT_ID`/`SHOP_MERCHANT_APP_TOKEN` in `shop.thegift.lk/.env`.
3. Confirm `CUSTOM_SANDBOX_KEY` is set on the target `thegift.lk` environment and `php artisan sandbox:seed-test-vouchers` has been run there.
4. Success walkthrough: Buy Now → Samsung S26 Ultra qty 1 → Pay Now → redeem with `TGVC-SANDBOX-2500`/`000000` on thegift.lk's page → land back on `/return` showing "Payment Successful" → check `/webhook-logs` shows a `status: success` row with matching `order_reference`.
5. Failure walkthrough: Buy Now → iPhone 17 Pro Plus qty 3 (LKR 4,350) → Pay Now → redeem with `TGVC-SANDBOX-3000`/`000000` → land back on `/return` showing "Payment Failed" → check `/webhook-logs` shows a `status: failed` row.

## 6. Phased build (per explicit request)

**Phase 1 (this plan's first task):** `laravel new` scaffold, strip the default Vite/Inertia frontend scaffolding, confirm the app boots at `shop.thegift.lk` (Valet-style, matching siblings), a trivial placeholder homepage. Stop here — the developer makes the first commit to the `shop.thegift.lk` repo themselves.

**Phase 2 (resumes only once the developer confirms, and after they've created whatever branches they want):** everything in section 3 — products, checkout, `/merchant/pay` integration, webhook receiver + log table + log viewer, return page.
