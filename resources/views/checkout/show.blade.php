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
