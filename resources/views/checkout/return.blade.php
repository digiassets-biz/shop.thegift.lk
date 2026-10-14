<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Status - shop.thegift.lk</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="return-page" id="return-page">
        <div id="return-waiting">
            <div class="spinner" aria-hidden="true"></div>
            <h1 class="waiting">Confirming your payment...</h1>
            <p>Waiting for the background payment notification to arrive. This is usually instant.</p>
        </div>

        <div id="return-resolved" hidden>
            <h1 id="return-heading"></h1>
            <p id="return-validation"></p>
        </div>

        <div id="return-timeout" hidden>
            <h1 class="waiting">Still waiting...</h1>
            <p>The payment notification hasn't arrived yet. This page will keep checking automatically - you don't need to do anything.</p>
        </div>

        <p>Order reference: {{ $orderReference }}</p>
        <p>Payment UUID: {{ $uuid }}</p>

        <p><a href="{{ route('products.index') }}">&larr; Back to store</a></p>
        <p><a href="{{ route('webhook-logs.index') }}">View webhook logs</a></p>
    </main>

    <script>
        (function () {
            const uuid = @json($uuid);
            const validated = @json($validated);
            const statusUrl = @json(route('checkout.return-status'));

            const page = document.getElementById('return-page');
            const waitingEl = document.getElementById('return-waiting');
            const resolvedEl = document.getElementById('return-resolved');
            const timeoutEl = document.getElementById('return-timeout');
            const headingEl = document.getElementById('return-heading');
            const validationEl = document.getElementById('return-validation');

            const POLL_INTERVAL_MS = 1500;
            const TIMEOUT_AFTER_MS = 45000;
            const startedAt = Date.now();

            function showResolved(status) {
                const success = status === 'success';
                page.classList.add('status-' + status);
                headingEl.textContent = success ? 'Payment Successful' : 'Payment Failed';
                headingEl.className = success ? 'success' : 'failed';

                if (validated) {
                    validationEl.textContent = '✓ Validation code matches what was issued at checkout.';
                    validationEl.className = 'validation-ok';
                } else {
                    validationEl.textContent = '⚠ Validation code could not be confirmed against this session (session may have expired, or this link was opened out of order).';
                    validationEl.className = 'validation-mismatch';
                }

                waitingEl.hidden = true;
                timeoutEl.hidden = true;
                resolvedEl.hidden = false;
                document.title = (success ? 'Payment Successful' : 'Payment Failed') + ' - shop.thegift.lk';
            }

            function poll() {
                if (!uuid) {
                    return;
                }

                fetch(statusUrl + '?uuid=' + encodeURIComponent(uuid), { headers: { Accept: 'application/json' } })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.found) {
                            showResolved(data.status);
                            return;
                        }

                        if (Date.now() - startedAt >= TIMEOUT_AFTER_MS) {
                            waitingEl.hidden = true;
                            timeoutEl.hidden = false;
                        }

                        setTimeout(poll, POLL_INTERVAL_MS);
                    })
                    .catch(() => {
                        setTimeout(poll, POLL_INTERVAL_MS);
                    });
            }

            poll();
        })();
    </script>
</body>
</html>
