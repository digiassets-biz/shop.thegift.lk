# shop.thegift.lk — Pay by Plugin Test Storefront Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a minimal Laravel storefront (shop.thegift.lk) that calls TheGift's real Pay by Plugin API as a genuine third-party merchant would, so the developer can experience a real successful payment and a real failed payment end to end, reusable across every environment.

**Architecture:** Laravel 12 / PHP 8.2, plain Blade, no Inertia/React/Vite build pipeline — one hand-written CSS file served directly from `public/`. Two hardcoded products, a checkout flow that calls `api.thegift.lk`'s `/merchant/pay` endpoint and redirects to the returned hosted payment link, a CSRF-exempt webhook receiver backed by one `webhook_logs` table, and a return-redirect landing page that cross-checks `merchant_validation_code` against session state.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, plain CSS (no npm/node required to run).

**Spec:** `docs/superpowers/specs/2026-09-29-shop-thegift-lk-integration-design.md`

## Global Constraints

- No PHPUnit anywhere in this repo. Verification is `php -l` on every new/changed PHP file, `php artisan tinker` for backend logic worth exercising in isolation, and manual browser walkthroughs for anything UI-facing. Never run `php artisan test` or phpunit.
- Never run `git add`/`git commit`/`git push` at any point in this plan, in either phase. The user commits everything themselves in every repo in this workspace — this is a hard rule, not a default.
- `php artisan migrate` is fine to run (brand new app, no data at risk). No other destructive DB operation.
- Laravel version: `^12.0`. PHP version: `^8.2`. These match every sibling app in this workspace (thegift.lk, merchant.thegift.lk, admin.thegift.lk, api.thegift.lk).
- No Inertia, no React, no Vite build step. Plain Blade + one static CSS file.
- **Phase 1 (Task 1 only) ends in a mandatory hard stop.** Do not begin Phase 2 (Task 2 onward) until the user explicitly says they're ready — they are making the first commit to this repository themselves, on their own timeline, possibly after creating additional branches. Do not treat "plan approved" as authorization to run Task 2 — a separate, later go-ahead is required.

---

### Task 1: Scaffold the Laravel app (Phase 1 — ends in a hard stop)

**Files:**
- Create: entire Laravel 12 application skeleton at the repo root (`D:\Companies\TheGift\Tech Operations\Software Developments\Repos\workspace\shop.thegift.lk`)
- Modify: `.gitignore` (already present in the repo — a fresh `laravel new` will offer to overwrite it; keep the existing one intact, since it already has the correct Laravel-appropriate entries)
- Delete: `resources/js/`, `vite.config.js`'s Vue/React plugin wiring (keep a minimal `vite.config.js` only if the installer requires one to exist, or remove entirely if not referenced anywhere), `package.json`'s frontend dependencies
- Create: `public/css/app.css` (empty placeholder file for now — filled in Task 2)
- Create: `resources/views/welcome.blade.php` (replaced with a trivial placeholder)

**Interfaces:**
- Consumes: nothing (first task in the repo)
- Produces: a booting Laravel 12 app at this path, ready for Task 2 to add routes/controllers/views into. No products, no routes beyond the default `/`, no config beyond Laravel's defaults.

- [ ] **Step 1: Scaffold the app**

The repo directory already exists (with a `.gitignore` and the spec doc under `docs/superpowers/specs/`) but has no Laravel app yet. Run the installer directly into this existing directory:

```bash
cd "D:\Companies\TheGift\Tech Operations\Software Developments\Repos\workspace\shop.thegift.lk"
composer create-project laravel/laravel:^12.0 . --prefer-dist
```

(Using `composer create-project` rather than the `laravel new` CLI installer avoids requiring the global `laravel` installer tool to be present — `composer` is already a hard dependency of every sibling app in this workspace.)

If the installer complains the directory isn't empty (because of the existing `.gitignore` and `docs/` folder), that's expected — Composer's `create-project` with `.` as the target still works as long as there's no existing `composer.json`; if it refuses, move `.gitignore` and `docs/` aside temporarily, run the install, then move them back and let the installer's own `.gitignore` be replaced by the pre-existing one (Step 2 covers this explicitly).

- [ ] **Step 2: Restore the original `.gitignore` and confirm `docs/` survived**

```bash
git status
```

Confirm `docs/superpowers/specs/2026-09-29-shop-thegift-lk-integration-design.md` is still present and that `.gitignore` still contains the original entries (it already covers `/vendor/`, `node_modules/`, `.env`, `/public/build`, etc. — appropriate for this project, no changes needed here).

- [ ] **Step 3: Strip the default frontend build scaffolding**

Delete these (the spec calls for plain Blade + one static CSS file, no build pipeline):

```bash
rm -rf resources/js
rm -f vite.config.js
```

Edit `package.json` — remove the `"scripts"` and `"devDependencies"` entries entirely (or delete `package.json` outright if nothing else references it). Since nothing in this app will ever run `npm install`, an empty/missing `package.json` is fine.

Edit `resources/views/welcome.blade.php` — replace the entire default Laravel welcome page with:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>shop.thegift.lk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <h1>shop.thegift.lk</h1>
    <p>Pay by Plugin test storefront — under construction.</p>
</body>
</html>
```

Create an empty placeholder stylesheet so the `<link>` tag above doesn't 404:

```bash
mkdir -p public/css
touch public/css/app.css
```

- [ ] **Step 4: Remove any remaining Vite reference from the base layout / bootstrapping**

Check `resources/views/welcome.blade.php` no longer contains `@vite(...)` (it shouldn't, since it was fully replaced in Step 3). Check `composer.json`'s `"scripts"` section — Laravel 12's default `composer.json` includes a `post-create-project-cmd` that may reference `npm install && npm run build`; if present, remove that line so `composer install` never tries to invoke npm:

```bash
grep -n "npm" composer.json
```

If any line references `npm`, remove it from the `scripts` block in `composer.json`.

- [ ] **Step 5: Set up `.env`**

```bash
cp .env.example .env
php artisan key:generate
```

Set `APP_NAME="shop.thegift.lk"` and `APP_URL=http://shop.thegift.lk` in `.env` (matching the Valet-style local domain convention every sibling app in this workspace already uses).

- [ ] **Step 6: Confirm the app boots**

```bash
php -l resources/views/welcome.blade.php
php artisan serve --port=8123 &
sleep 2
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8123/
```

Expected: `200`. Then stop the server:

```bash
kill %1
```

(`php artisan serve` on an explicit port is used here specifically so this verification doesn't depend on Valet/hosts-file DNS being configured for `shop.thegift.lk` yet — once the developer's environment has that domain parked the same way the sibling apps are, `http://shop.thegift.lk` will work directly too, but that's outside this plan's control.)

- [ ] **Step 7: HARD STOP — do not proceed to Task 2**

This is a mandatory checkpoint. Report to the user that Phase 1 is complete: a booting Laravel 12 app with no frontend build step, a placeholder homepage, ready for their first commit. **Do not run `git add`/`git commit` — the user is making this first commit themselves.** Do not begin Task 2 (or any later task) until the user explicitly says they're ready for Phase 2 — they may create additional branches on their end first, on their own timeline. Treat silence or an unrelated message as "not yet," not as implicit approval to continue.

---

## Phase 2

*(Everything below only begins once the user has explicitly confirmed they're ready, per Task 1 Step 7.)*

### Task 2: Hardcoded products, homepage, and checkout page (read-only parts)

**Files:**
- Create: `config/products.php`
- Create: `app/Http/Controllers/ProductController.php`
- Create: `app/Http/Controllers/CheckoutController.php` (this task: only the `show` action; `pay` is Task 3)
- Create: `resources/views/products/index.blade.php`
- Create: `resources/views/checkout/show.blade.php`
- Modify: `routes/web.php`
- Modify: `public/css/app.css` (fill in real styling)

**Interfaces:**
- Consumes: nothing new from Task 1 beyond the booting app itself.
- Produces: `config('products')` returns the array below, keyed by slug. `CheckoutController::show(Request $request)` reads `product` (slug) and `qty` (int) query params — this exact pair of query param names is what Task 3's "Pay Now" form and any later task must also use.

- [ ] **Step 1: Define the hardcoded products**

Create `config/products.php`:

```php
<?php

return [
    'samsung-s26-ultra' => [
        'name' => 'Samsung S26 Ultra',
        'price' => 1200,
    ],
    'iphone-17-pro-plus' => [
        'name' => 'iPhone 17 Pro Plus',
        'price' => 1450,
    ],
];
```

- [ ] **Step 2: `php -l` the new config file**

```bash
php -l config/products.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: `ProductController@index`**

Create `app/Http/Controllers/ProductController.php`:

```php
<?php

namespace App\Http\Controllers;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'products' => config('products'),
        ]);
    }
}
```

- [ ] **Step 4: Products view with qty stepper and Buy Now**

Create `resources/views/products/index.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>shop.thegift.lk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="site-header">
        <h1>shop.thegift.lk</h1>
        <p class="tagline">Pay by Plugin test storefront</p>
    </header>

    <main class="product-grid">
        @foreach ($products as $slug => $product)
            <form class="product-card" action="{{ route('checkout.show') }}" method="GET">
                <input type="hidden" name="product" value="{{ $slug }}">
                <h2>{{ $product['name'] }}</h2>
                <p class="price">LKR {{ number_format($product['price'], 2) }}</p>
                <label for="qty-{{ $slug }}">Quantity</label>
                <input type="number" id="qty-{{ $slug }}" name="qty" value="1" min="1" max="5">
                <button type="submit">Buy Now</button>
            </form>
        @endforeach
    </main>
</body>
</html>
```

- [ ] **Step 5: `CheckoutController@show`**

Create `app/Http/Controllers/CheckoutController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
```

Note: `config('shop.customer_phone')`/`config('shop.customer_email')` don't exist yet — they're wired up in Task 5. For this task, hardcode fallback values directly so the page renders correctly before Task 5 runs:

```php
'customerPhone' => config('shop.customer_phone', '0771234567'),
'customerEmail' => config('shop.customer_email', 'customer@example.com'),
```

- [ ] **Step 6: Checkout view**

Create `resources/views/checkout/show.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout — shop.thegift.lk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="checkout">
        <h1>Checkout</h1>

        @if ($error)
            <p class="error">{{ $error }}</p>
        @endif

        <section class="order-summary">
            <h2>Order Summary</h2>
            <p>{{ $product['name'] }} &times; {{ $qty }}</p>
            <p class="amount">LKR {{ number_format($amount, 2) }}</p>
        </section>

        <section class="billing-info">
            <h2>Billing Info</h2>
            <p>Phone: {{ $customerPhone }}</p>
            <p>Email: {{ $customerEmail }}</p>
        </section>

        <section class="payment-method">
            <h2>Payment Method</h2>
            <p>TheGift Voucher</p>
        </section>

        <form action="{{ route('checkout.pay') }}" method="POST">
            @csrf
            <input type="hidden" name="product" value="{{ $slug }}">
            <input type="hidden" name="qty" value="{{ $qty }}">
            <button type="submit">Pay Now</button>
        </form>
    </main>
</body>
</html>
```

- [ ] **Step 7: Routes**

In `routes/web.php`, replace the default `/` route and add checkout's GET route:

```php
<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
```

(The `POST /checkout/pay` route is added in Task 3, once `CheckoutController::pay` exists — adding the route before the action exists would break route caching/route listing.)

- [ ] **Step 8: Fill in real CSS**

Replace the placeholder `public/css/app.css` with real (simple) styling — a readable sans-serif, a centered max-width container, card-style product tiles, clear spacing for the checkout sections. No specific content is prescribed here beyond "make it legible and presentable" — this is a test tool, not a design deliverable, so keep it plain.

- [ ] **Step 9: `php -l` every new PHP file**

```bash
php -l app/Http/Controllers/ProductController.php
php -l app/Http/Controllers/CheckoutController.php
php -l routes/web.php
```

Expected: `No syntax errors detected` for all three.

- [ ] **Step 10: Manual verification**

```bash
php artisan serve --port=8123 &
sleep 2
curl -s http://127.0.0.1:8123/ | grep -o "Samsung S26 Ultra"
curl -s "http://127.0.0.1:8123/checkout?product=samsung-s26-ultra&qty=2" | grep -o "LKR 2,400.00"
kill %1
```

Expected: both `grep` calls print a match (confirms the homepage lists the product, and checkout correctly computes qty × price = 2400).

---

### Task 3: `/merchant/pay` integration (CheckoutController@pay)

**Files:**
- Modify: `app/Http/Controllers/CheckoutController.php` (add `pay` action)
- Modify: `routes/web.php` (add `POST /checkout/pay`)
- Create: `config/shop.php`

**Interfaces:**
- Consumes: `CheckoutController::show`'s `product`/`qty` query param contract from Task 2. `config('products')` from Task 2.
- Produces: on success, redirects the browser to the real `payment_link` returned by `api.thegift.lk`, and stores `session(['shop_order_reference' => ..., 'shop_merchant_validation_code' => ...])` — Task 6 (`ReturnController`) consumes these exact session keys to cross-check `merchant_validation_code` on the way back.

- [ ] **Step 1: `config/shop.php`**

Create `config/shop.php`:

```php
<?php

return [
    'api_base_url' => env('SHOP_API_BASE_URL', 'http://api.thegift.lk'),
    'merchant_id' => env('SHOP_MERCHANT_ID'),
    'merchant_app_token' => env('SHOP_MERCHANT_APP_TOKEN'),
    'customer_phone' => env('SHOP_CUSTOMER_PHONE', '0771234567'),
    'customer_email' => env('SHOP_CUSTOMER_EMAIL', 'customer@example.com'),
    'custom_param_1' => env('SHOP_CUSTOM_PARAM_1', 'shop-test'),
    'custom_param_2' => env('SHOP_CUSTOM_PARAM_2', 'v1'),
];
```

- [ ] **Step 2: `php -l` the config file**

```bash
php -l config/shop.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: `CheckoutController@pay`**

Add to `app/Http/Controllers/CheckoutController.php` (alongside the existing `show` method from Task 2):

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

public function pay(Request $request)
{
    $slug = $request->input('product');
    $qty = max(1, min(5, (int) $request->input('qty', 1)));

    $product = config("products.$slug");
    abort_if(!$product, 404);

    $amount = $product['price'] * $qty;
    $orderReference = 'SHOP-' . now()->timestamp;

    $response = Http::timeout(15)->post(rtrim(config('shop.api_base_url'), '/') . '/merchant/pay', [
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
```

Note: `checkout.return` and `webhook.notify` route names don't exist until Task 6 and Task 4 respectively. This task's routes file change (Step 4) only adds `checkout.pay` — the `route('webhook.notify')`/`route('checkout.return')` calls inside `pay()` will throw `RouteNotFoundException` until those later tasks add their routes, which is expected and fine since this task's own verification (Step 6) doesn't invoke `pay()` end-to-end yet, only checks the code is syntactically correct and wired. Task 6's verification step is where the full chain is exercised together for real.

Also update the `show` method to no longer hardcode fallback values now that `config('shop.php')` is real:

```php
'customerPhone' => config('shop.customer_phone'),
'customerEmail' => config('shop.customer_email'),
```

(Since `config/shop.php` now defines defaults for both via `env(..., 'default')`, `config('shop.customer_phone')` always resolves to something even before `.env` is customized.)

- [ ] **Step 4: Add the `checkout.pay` route**

In `routes/web.php`:

```php
Route::post('/checkout/pay', [CheckoutController::class, 'pay'])->name('checkout.pay');
```

- [ ] **Step 5: `.env.example` entries**

Add to `.env.example` (so future clones of this repo — including the developer's own re-clones in other environments — have the right keys documented, blank by default):

```
SHOP_API_BASE_URL=http://api.thegift.lk
SHOP_MERCHANT_ID=
SHOP_MERCHANT_APP_TOKEN=
SHOP_CUSTOMER_PHONE=0771234567
SHOP_CUSTOMER_EMAIL=customer@example.com
SHOP_CUSTOM_PARAM_1=shop-test
SHOP_CUSTOM_PARAM_2=v1
```

Add the same block to the local `.env` (values can stay blank/placeholder for now — filling in real `SHOP_MERCHANT_ID`/`SHOP_MERCHANT_APP_TOKEN` is a Task 7 step, done by the user).

- [ ] **Step 6: `php -l` and route-list verification**

```bash
php -l app/Http/Controllers/CheckoutController.php
php -l config/shop.php
php -l routes/web.php
php artisan route:list --name=checkout
```

Expected: no syntax errors, and `route:list` shows `checkout.show` (GET) and `checkout.pay` (POST).

- [ ] **Step 7: Tinker-verify the HTTP call shape without a real merchant token**

Since `SHOP_MERCHANT_ID`/`SHOP_MERCHANT_APP_TOKEN` aren't filled in yet, a real call will correctly fail validation on `api.thegift.lk`'s side (error code 101/102) — this still proves the request reaches the endpoint with the right shape:

```bash
php artisan tinker --execute="
\$response = Illuminate\Support\Facades\Http::timeout(15)->post(rtrim(config('shop.api_base_url'), '/') . '/merchant/pay', [
    'merchant_id' => 'placeholder',
    'merchant_app_token' => 'placeholder',
    'currency' => 'LKR',
    'amount' => 1200,
    'order_reference' => 'SHOP-TEST-' . now()->timestamp,
    'customer_phone_number' => config('shop.customer_phone'),
    'customer_email' => config('shop.customer_email'),
    'notify_url' => 'http://shop.thegift.lk/notify',
    'return_url' => 'http://shop.thegift.lk/return',
    'custom_param_1' => config('shop.custom_param_1'),
    'custom_param_2' => config('shop.custom_param_2'),
]);
echo \$response->status() . PHP_EOL;
print_r(\$response->json());
"
```

Expected: HTTP `422` with `status: 101` (invalid `merchant_app_token`) — confirms `api.thegift.lk` is reachable from this app and the request shape is accepted far enough to reach field-level validation, not rejected as malformed. If `api.thegift.lk` isn't running/reachable in this dev environment, this step will instead show a connection error — note that in your report rather than treating it as a code bug, since it's an environment/networking concern outside this app.

---

### Task 4: Webhook receiver, `webhook_logs` table, and log viewer

**Files:**
- Create: `database/migrations/<timestamp>_create_webhook_logs_table.php`
- Create: `app/Models/WebhookLog.php`
- Create: `app/Http/Controllers/WebhookController.php`
- Create: `app/Http/Controllers/WebhookLogController.php`
- Create: `resources/views/webhook-logs/index.blade.php`
- Modify: `routes/web.php`
- Modify: `bootstrap/app.php` (CSRF exemption for `/notify`)

**Interfaces:**
- Consumes: nothing from earlier tasks directly (independent of Task 2/3's checkout flow at the code level, though it's only ever exercised for real once Task 3's `notify_url` actually points at it).
- Produces: `POST /notify` (route name `webhook.notify`) — the exact route name Task 3's `CheckoutController::pay` already calls via `route('webhook.notify')`. `WebhookLog` model with columns `uuid`, `order_reference`, `status`, `amount`, `currency`, `merchant_validation_code`, `payload` (json).

- [ ] **Step 1: Migration**

```bash
php artisan make:migration create_webhook_logs_table
```

Edit the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `webhook_logs` table created, no errors.

- [ ] **Step 3: `WebhookLog` model**

Create `app/Models/WebhookLog.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'uuid', 'order_reference', 'status', 'amount', 'currency',
        'merchant_validation_code', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];
}
```

- [ ] **Step 4: `WebhookController@notify`**

Create `app/Http/Controllers/WebhookController.php`:

```php
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
```

- [ ] **Step 5: `WebhookLogController@index`**

Create `app/Http/Controllers/WebhookLogController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;

class WebhookLogController extends Controller
{
    public function index()
    {
        return view('webhook-logs.index', [
            'logs' => WebhookLog::orderByDesc('created_at')->get(),
        ]);
    }
}
```

- [ ] **Step 6: Log viewer view**

Create `resources/views/webhook-logs/index.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Webhook Logs — shop.thegift.lk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="webhook-logs">
        <h1>Webhook Logs</h1>
        <p><a href="{{ route('products.index') }}">&larr; Back to store</a></p>

        @forelse ($logs as $log)
            <article class="webhook-log-entry status-{{ $log->status }}">
                <header>
                    <strong>{{ strtoupper($log->status ?? 'unknown') }}</strong>
                    <time>{{ $log->created_at->toDayDateTimeString() }}</time>
                </header>
                <p>Order: {{ $log->order_reference }}</p>
                <p>Amount: {{ $log->currency }} {{ number_format($log->amount ?? 0, 2) }}</p>
                <p>UUID: {{ $log->uuid }}</p>
                <p>Validation code: {{ $log->merchant_validation_code }}</p>
            </article>
        @empty
            <p>No webhook calls received yet.</p>
        @endforelse
    </main>
</body>
</html>
```

- [ ] **Step 7: Routes**

In `routes/web.php`:

```php
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookLogController;

Route::post('/notify', [WebhookController::class, 'notify'])->name('webhook.notify');
Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
```

- [ ] **Step 8: CSRF-exempt `/notify`**

`thegift.lk` calls this route server-to-server with no CSRF token, so it must bypass Laravel's CSRF middleware. In `bootstrap/app.php`, inside the `->withMiddleware(function (Middleware $middleware) { ... })` block (add the block if the default skeleton doesn't already have one), add:

```php
$middleware->validateCsrfTokens(except: [
    'notify',
]);
```

- [ ] **Step 9: `php -l` every new PHP file**

```bash
php -l database/migrations/*_create_webhook_logs_table.php
php -l app/Models/WebhookLog.php
php -l app/Http/Controllers/WebhookController.php
php -l app/Http/Controllers/WebhookLogController.php
php -l routes/web.php
php -l bootstrap/app.php
```

Expected: `No syntax errors detected` for all.

- [ ] **Step 10: Tinker-verify the webhook receiver end-to-end (without a real thegift.lk call)**

```bash
php artisan serve --port=8123 &
sleep 2
curl -s -X POST http://127.0.0.1:8123/notify \
  -H "Content-Type: application/json" \
  -d '{"uuid":"test-uuid-1","order_reference":"SHOP-TEST-1","status":"success","amount":1200,"currency":"LKR","merchant_validation_code":"TESTCODE123","custom_param_1":"shop-test","custom_param_2":"v1"}'
kill %1
```

Expected: `{"ok":true}` response, no CSRF 419 error.

```bash
php artisan tinker --execute="echo App\Models\WebhookLog::where('order_reference', 'SHOP-TEST-1')->exists() ? 'FOUND' : 'MISSING';"
```

Expected: `FOUND`.

- [ ] **Step 11: Clean up the test row**

```bash
php artisan tinker --execute="App\Models\WebhookLog::where('order_reference', 'SHOP-TEST-1')->delete();"
```

---

### Task 5: Return page (`ReturnController@show`) with validation-code cross-check

**Files:**
- Create: `app/Http/Controllers/ReturnController.php`
- Create: `resources/views/checkout/return.blade.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: the session keys `shop_order_reference` and `shop_merchant_validation_code`, written by `CheckoutController::pay` in Task 3.
- Produces: `GET /return` (route name `checkout.return`) — the exact route name Task 3's `CheckoutController::pay` already calls via `route('checkout.return')`.

- [ ] **Step 1: `ReturnController@show`**

Create `app/Http/Controllers/ReturnController.php`:

```php
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
```

Note: `session()->pull()` (not `get()`) is used deliberately — the validation-code check is meant to be single-use per payment attempt, matching how a real merchant integration would consume it once and not leave it lingering in session state for a later unrelated request to accidentally match against.

- [ ] **Step 2: Return view**

Create `resources/views/checkout/return.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment {{ $status === 'success' ? 'Successful' : 'Failed' }} — shop.thegift.lk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="return-page status-{{ $status }}">
        @if ($status === 'success')
            <h1 class="success">Payment Successful</h1>
        @else
            <h1 class="failed">Payment Failed</h1>
        @endif

        <p>Order reference: {{ $orderReference }}</p>
        <p>Payment UUID: {{ $uuid }}</p>

        @if ($validated)
            <p class="validation-ok">✓ Validation code matches what was issued at checkout.</p>
        @else
            <p class="validation-mismatch">⚠ Validation code could not be confirmed against this session (session may have expired, or this link was opened out of order).</p>
        @endif

        <p><a href="{{ route('products.index') }}">&larr; Back to store</a></p>
        <p><a href="{{ route('webhook-logs.index') }}">View webhook logs</a></p>
    </main>
</body>
</html>
```

- [ ] **Step 3: Route**

In `routes/web.php`:

```php
use App\Http\Controllers\ReturnController;

Route::get('/return', [ReturnController::class, 'show'])->name('checkout.return');
```

- [ ] **Step 4: `php -l` verification**

```bash
php -l app/Http/Controllers/ReturnController.php
php -l routes/web.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Tinker/curl-verify the matched and mismatched cases**

Matched case — simulate a session already carrying the expected values:

```bash
php artisan tinker --execute="
session(['shop_order_reference' => 'SHOP-TEST-2', 'shop_merchant_validation_code' => 'MATCHCODE']);
\$controller = new App\Http\Controllers\ReturnController();
\$request = Illuminate\Http\Request::create('/return', 'GET', [
    'status' => 'success', 'uuid' => 'u1', 'order_reference' => 'SHOP-TEST-2', 'merchant_validation_code' => 'MATCHCODE',
]);
\$request->setLaravelSession(session()->driver());
\$view = \$controller->show(\$request);
echo \$view->getData()['validated'] ? 'VALIDATED' : 'NOT VALIDATED';
"
```

Expected: `VALIDATED`.

Since this direct-instantiation tinker check exercises Laravel's session plumbing in an unusual way (no real HTTP request/response cycle), treat a `NOT VALIDATED` result here as inconclusive rather than a definite bug — the real, authoritative check is the full browser walkthrough in Task 7, where `pay()` and `return.show()` run as two genuinely separate HTTP requests sharing a real cookie-backed session. This tinker step is a quick sanity check on the comparison logic itself, not a substitute for that walkthrough.

- [ ] **Step 6: Route-list sanity check**

```bash
php artisan route:list --name=checkout
```

Expected: `checkout.show`, `checkout.pay`, `checkout.return` all present.

---

### Task 6: Final wiring review and guided manual verification

**Files:** none (verification only, plus any small fixes surfaced by this review)

**Interfaces:**
- Consumes: everything produced by Tasks 2-5.
- Produces: a fully working app, confirmed end to end by the user's own browser walkthrough.

- [ ] **Step 1: Full route list review**

```bash
php artisan route:list
```

Confirm all 6 routes are present and correctly named: `products.index` (GET /), `checkout.show` (GET /checkout), `checkout.pay` (POST /checkout/pay), `webhook.notify` (POST /notify), `checkout.return` (GET /return), `webhook-logs.index` (GET /webhook-logs).

- [ ] **Step 2: `php -l` every PHP file in the app one more time, as a final sweep**

```bash
find app config routes -name "*.php" -exec php -l {} \;
```

Expected: `No syntax errors detected` for every file, no `Errors parsing` lines anywhere in the output.

- [ ] **Step 3: Tell the user this app is ready for real credentials**

At this point the app is fully built but cannot complete a real payment yet — `SHOP_MERCHANT_ID`/`SHOP_MERCHANT_APP_TOKEN` in `.env` are still blank/placeholder. Report to the user that Task 6 (and the plan) is code-complete, and that the two guided walkthroughs below are manual steps for them to run themselves (or to run together with the agent watching), since they require:
1. An approved app token from merchant.thegift.lk's Developer Options → Integrations page (their own action, outside this app).
2. `CUSTOM_SANDBOX_KEY` configured on the target `thegift.lk` environment, and `php artisan sandbox:seed-test-vouchers` already run there (also outside this app — a property of whichever `thegift.lk` environment they're pointing at).

- [ ] **Step 4: Guided success walkthrough (manual, with the user)**

1. Confirm `SHOP_MERCHANT_ID` and `SHOP_MERCHANT_APP_TOKEN` are set to real, approved values in `shop.thegift.lk/.env`.
2. Start the app (`php artisan serve` or via Valet if `shop.thegift.lk` is parked).
3. Visit the homepage, click "Buy Now" on Samsung S26 Ultra at qty 1 (LKR 1,200).
4. On the checkout page, click "Pay Now".
5. Confirm the browser lands on a `thegift.lk/pay-by-plugin/<uuid>` page.
6. Redeem with voucher code `TGVC-SANDBOX-2500`, PIN `000000`.
7. Confirm the browser is redirected back to `shop.thegift.lk/return` showing "Payment Successful" with a matching validation-code confirmation.
8. Visit `/webhook-logs` and confirm a new entry with `status: success` and the same `order_reference`.

- [ ] **Step 5: Guided failure walkthrough (manual, with the user)**

1. From the homepage, click "Buy Now" on iPhone 17 Pro Plus, set quantity to 3 (LKR 4,350 — over the LKR 3,000 sandbox-failure threshold).
2. On the checkout page, click "Pay Now".
3. On the `thegift.lk` redemption page, redeem with voucher code `TGVC-SANDBOX-3000`, PIN `000000`.
4. Confirm the browser is redirected back to `shop.thegift.lk/return` showing "Payment Failed".
5. Visit `/webhook-logs` and confirm a new entry with `status: failed` and the matching `order_reference`.

- [ ] **Step 6: Report completion**

Summarize to the user: both walkthroughs confirmed (or, if either surfaced an issue, describe exactly what broke and where — do not mark this task complete on a partial/failed walkthrough). Remind them this app remains available for repeating both walkthroughs against any other environment, by pointing `SHOP_API_BASE_URL` and the merchant credentials at that environment's values.
