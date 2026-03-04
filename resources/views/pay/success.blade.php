<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Receipt {{ $order->md5 }}</title>
  <style>
    :root { --border:#ddd; --muted:#666; --success:#0a7a2f; --success-bg:#e9f8ef; --code:#f6f6f6; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 24px; }
    .wrap { max-width: 760px; margin: 0 auto; }
    .card { border:1px solid var(--border); border-radius:14px; padding:14px 16px; }
    .ok { border-color: rgba(10,122,47,.35); background: var(--success-bg); }
    .big { font-weight:900; font-size:22px; color: var(--success); }
    .muted { color: var(--muted); font-size:13px; }
    code { background: var(--code); padding:2px 6px; border-radius:6px; }
    .row { display:flex; justify-content:space-between; padding:10px 12px; border:1px solid var(--border); border-radius:12px; margin-top:8px; background:#fff; }
    .bar { margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap; }
    .btn { display:inline-block; padding:10px 12px; border-radius:10px; background:#111; color:#fff; text-decoration:none; }
    pre { margin-top: 10px; background: var(--code); padding: 10px; border-radius: 10px; overflow:auto; }
    #qrcode { background:#fff; padding: 8px; border-radius: 12px; border: 1px solid var(--border); opacity: .25; filter: grayscale(100%); }
  </style>
</head>
<body>
  @php
    $items = json_decode($order->items_json ?? '[]', true) ?: [];
    $paidData = $order->paid_data_json ? json_decode($order->paid_data_json, true) : null;

    $payer = $paidData['fromAccount'] ?? $paidData['sender'] ?? $paidData['payer'] ?? $paidData['payerAccount'] ?? null;
    $time  = $paidData['transactionDate'] ?? $paidData['timestamp'] ?? optional($order->paid_at)->toISOString();
    $tx    = $paidData['transactionId'] ?? $paidData['txnId'] ?? $paidData['hash'] ?? $paidData['reference'] ?? null;
  @endphp

  <div class="wrap">
    <div class="card ok">
      <div class="big">Payment successful</div>
      <div class="muted">Thank you — your order is confirmed.</div>

      <div style="margin-top:12px"><b>Invoice</b>: <code>{{ $order->invoice_id }}</code></div>
      <div style="margin-top:8px"><b>Total</b>: <code>{{ $order->total_khr }} KHR</code></div>
      <div style="margin-top:8px"><b>Paid by</b>: <code>{{ $payer ?? '(unknown)' }}</code></div>
      <div style="margin-top:8px"><b>Time</b>: <code>{{ $time ?? '(unknown)' }}</code></div>
      <div style="margin-top:8px"><b>Transaction</b>: <code>{{ $tx ?? '(unknown)' }}</code></div>
      <div style="margin-top:8px"><b>MD5</b>: <code>{{ $order->md5 }}</code></div>

      <h3 style="margin:14px 0 8px">Order summary</h3>
      @foreach($items as $i)
        <div class="row">
          <div><b>{{ $i['name'] ?? 'Item' }}</b> <span class="muted">x{{ $i['qty'] ?? 1 }}</span></div>
          <div><b>{{ ($i['price_khr'] ?? 0) * ($i['qty'] ?? 1) }} KHR</b></div>
        </div>
      @endforeach

      <div style="margin-top:14px">
        <div class="muted">Paid QR (disabled)</div>
        <div id="qrcode"></div>
      </div>

      <div class="bar">
        <a class="btn" href="{{ route('store') }}">Back to store</a>
      </div>

      <pre>{{ json_encode($paidData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    const khqr = @json($order->khqr_string);
    new QRCode(document.getElementById('qrcode'), { text: khqr, width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
  </script>
</body>
</html>
