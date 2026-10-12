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
