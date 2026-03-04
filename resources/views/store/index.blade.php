<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Store</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 24px; }
    .wrap { max-width: 820px; margin: 0 auto; }
    .top { display:flex; justify-content:space-between; align-items:baseline; gap: 10px; }
    .muted { color:#666; font-size:13px; }
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-top: 12px; }
    .card { border:1px solid #ddd; border-radius:14px; padding:14px; }
    .title { font-weight:900; font-size:17px; }
    button { padding:10px 12px; border-radius:10px; background:#111; color:#fff; border:1px solid #111; cursor:pointer; }
    input { padding:10px 12px; border-radius:10px; border:1px solid #ccc; width: 90px; }
    a { color: inherit; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h2 style="margin:0">Demo Store</h2>
        <div class="muted">Cheap demo prices (KHR) for easy testing</div>
      </div>
      <div style="text-align:right">
        <a href="{{ route('cart') }}">Cart</a>
        <div class="muted">Total: <b>{{ $cartTotal }} KHR</b></div>
      </div>
    </div>

    <div class="grid">
      @foreach($products as $p)
        <div class="card">
          <div class="title">{{ $p['name'] }}</div>
          <div class="muted">Price: <b>{{ $p['price_khr'] }} KHR</b></div>

          <form method="POST" action="{{ route('cart.add') }}" style="margin-top:10px; display:flex; gap:10px; align-items:center;">
            @csrf
            <input type="hidden" name="productId" value="{{ $p['id'] }}" />
            <input type="number" name="qty" value="1" min="1" max="99" />
            <button type="submit">Add to Cart</button>
          </form>
        </div>
      @endforeach
    </div>
  </div>
</body>
</html>
