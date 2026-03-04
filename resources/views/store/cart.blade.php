<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cart</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 24px; }
    .wrap { max-width: 820px; margin: 0 auto; }
    .muted { color:#666; font-size:13px; }
    .row { border:1px solid #ddd; border-radius:14px; padding:14px; margin: 10px 0; display:flex; justify-content:space-between; gap: 12px; }
    button { padding:10px 12px; border-radius:10px; cursor:pointer; border:1px solid #ccc; }
    .primary { background:#111; color:#fff; border-color:#111; width:100%; }
    .bar { display:flex; gap:10px; margin-top: 14px; }
    a { color: inherit; }
  </style>
</head>
<body>
  <div class="wrap">
    <div style="display:flex; justify-content:space-between; align-items:baseline;">
      <div>
        <h2 style="margin:0">Your Cart</h2>
        <div class="muted"><a href="{{ route('store') }}">← Continue shopping</a></div>
      </div>
      <div style="text-align:right">
        <div class="muted">Total</div>
        <div style="font-size:22px; font-weight:900;">{{ $total }} KHR</div>
      </div>
    </div>

    @if(count($items) === 0)
      <div class="muted" style="margin-top:14px;">Cart is empty.</div>
    @else
      @foreach($items as $i)
        <div class="row">
          <div>
            <div style="font-weight:900">{{ $i['name'] }} <span class="muted">x{{ $i['qty'] }}</span></div>
            <div class="muted">{{ $i['price_khr'] }} KHR each</div>
          </div>
          <div style="text-align:right">
            <div><b>{{ $i['price_khr'] * $i['qty'] }} KHR</b></div>
            <form method="POST" action="{{ route('cart.remove') }}" style="margin-top:8px;">
              @csrf
              <input type="hidden" name="productId" value="{{ $i['product_id'] }}" />
              <button type="submit">Remove</button>
            </form>
          </div>
        </div>
      @endforeach
    @endif

    <div class="bar">
      <form method="POST" action="{{ route('checkout') }}" style="flex:1;">
        @csrf
        <button class="primary" type="submit" {{ $total <= 0 ? 'disabled' : '' }}>Checkout with Bakong</button>
      </form>

      <form method="POST" action="{{ route('cart.clear') }}">
        @csrf
        <button type="submit">Clear</button>
      </form>
    </div>
  </div>
</body>
</html>
