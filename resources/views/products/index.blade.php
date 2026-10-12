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
