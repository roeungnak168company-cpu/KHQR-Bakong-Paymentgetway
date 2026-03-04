<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pay {{ $order->md5 }}</title>
  <style>
    :root { --border:#ddd; --muted:#666; --success:#0a7a2f; --success-bg:#e9f8ef; --warn:#8a5b00; --warn-bg:#fff6e5; --error:#b00020; --error-bg:#ffecee; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 24px; }
    .wrap { max-width: 560px; margin: 0 auto; }
    .box { border:1px solid var(--border); border-radius:12px; padding:12px 14px; margin: 12px 0 16px; display:flex; justify-content:space-between; gap: 12px; align-items:center; }
    .muted { color: var(--muted); font-size: 13px; }
    .success { border-color: rgba(10,122,47,.35); background: var(--success-bg); }
    .warning { border-color: rgba(138,91,0,.35); background: var(--warn-bg); }
    .error { border-color: rgba(176,0,32,.35); background: var(--error-bg); }
    .label { font-weight: 900; }
    .spinner { width:16px; height:16px; border:2px solid #ccc; border-top-color:#333; border-radius:999px; animation: spin .9s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .qrWrap { border:1px solid var(--border); border-radius:14px; padding:14px; display:flex; flex-direction:column; align-items:center; gap:10px; }
    #qrcode { background:#fff; padding: 8px; border-radius: 12px; border: 1px solid var(--border); }
    .qrMuted { opacity:.25; filter: grayscale(100%); }
    code { background:#f6f6f6; padding:2px 6px; border-radius:6px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin:0 0 12px">Scan to pay</h2>

    <div id="statusBox" class="box">
      <div>
        <div class="label" id="statusLabel">Checking payment…</div>
        <div class="muted" id="statusSub">This page updates automatically.</div>
      </div>
      <div class="spinner" id="spinner"></div>
    </div>

    <div class="qrWrap">
      <div id="qrcode"></div>
      <div class="muted">Amount: <code>{{ $order->total_khr }} KHR</code></div>
      <div class="muted">MD5: <code>{{ $order->md5 }}</code></div>
    </div>

    <div class="muted" style="margin-top:10px;">KHQR payload (debug):</div>
    <code style="display:block; width:100%; overflow:auto;">{{ $order->khqr_string }}</code>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
      const md5 = @json($order->md5);
      const expiresAtIso = @json(optional($order->expires_at)->toISOString());
      const khqr = @json($order->khqr_string);

      // Render QR in browser
      const qrel = document.getElementById('qrcode');
      const qrObj = new QRCode(qrel, { text: khqr, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });

      const statusBox = document.getElementById('statusBox');
      const statusLabel = document.getElementById('statusLabel');
      const statusSub = document.getElementById('statusSub');
      const spinner = document.getElementById('spinner');

      function setState(kind, label, sub) {
        statusBox.classList.remove('success','warning','error');
        if (kind) statusBox.classList.add(kind);
        statusLabel.textContent = label;
        statusSub.textContent = sub || '';
      }

      function stopSpinner() { spinner.style.display = 'none'; }

      function msUntilExpiry() {
        const exp = Date.parse(expiresAtIso || '');
        if (!Number.isFinite(exp)) return 0;
        return exp - Date.now();
      }

      let timer = null;

      async function checkOnce() {
        if (msUntilExpiry() <= 0) {
          if (timer) clearInterval(timer);
          stopSpinner();
          setState('warning', 'EXPIRED', 'This QR has expired.');
          qrel.classList.add('qrMuted');
          return;
        }

        try {
          const resp = await fetch('/api/qr/check', {
         method: 'POST',
         headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': @json(csrf_token())
         },
          body: JSON.stringify({ md5 })
        });

          const data = await resp.json().catch(() => ({}));

          if (resp.status === 410 || data?.expired === true) {
            if (timer) clearInterval(timer);
            stopSpinner();
            setState('warning', 'EXPIRED', 'This QR has expired.');
            qrel.classList.add('qrMuted');
            return;
          }

          if (!resp.ok) {
            stopSpinner();
            setState('error', 'ERROR', data?.error ? String(data.error) : ('HTTP ' + resp.status));
            return;
          }

          if (data?.paid === true) {
            if (timer) clearInterval(timer);
            stopSpinner();
            setState('success', 'PAID', 'Redirecting to receipt…');
            qrel.classList.add('qrMuted');
            setTimeout(() => {
              window.location.href = @json(url('/store/success')) + '/' + encodeURIComponent(md5);
            }, 800);
            return;
          }

          setState('', 'Waiting for payment…', 'Auto-checking every ~2.5 seconds');
        } catch (e) {
          stopSpinner();
          setState('error', 'ERROR', String(e));
        }
      }

      checkOnce();
      timer = setInterval(checkOnce, 2500);
    </script>
  </div>
</body>
</html>
