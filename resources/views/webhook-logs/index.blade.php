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
