<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BakongApiService;
use App\Services\KhqrBuilder;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function checkout(Request $request, KhqrBuilder $builder)
    {
        $cart = $request->session()->get('cart', []);

        $total = 0;
        foreach ($cart as $i) {
            $total += ((int)($i['price_khr'] ?? 0)) * ((int)($i['qty'] ?? 1));
        }

        if ($total <= 0) {
            return redirect()->route('cart');
        }

        $createdAt = now();
        $expiresAt = now()->addMinutes(10);

        $khqrString = $builder->buildKhqrString([
            'receiverBakongId' => env('MERCHANT_BAKONG_ID', ''),
            'amountKhr' => $total,
            'merchantName' => env('MERCHANT_NAME', 'Merchant'),
            'merchantCity' => env('MERCHANT_CITY', 'Phnom Penh'),
            'createdAtMs' => (string)$createdAt->getTimestampMs(),
            'expiresAtMs' => (string)$expiresAt->getTimestampMs(),
        ]);

        $md5 = md5($khqrString);
        $invoiceId = (string)Str::uuid();

        $order = Order::create([
            'md5' => $md5,
            'invoice_id' => $invoiceId,
            'khqr_string' => $khqrString,
            'items_json' => json_encode(array_values($cart), JSON_UNESCAPED_UNICODE),
            'total_khr' => $total,
            'currency' => 'KHR',
            'status' => 'CREATED',
            'expires_at' => $expiresAt,
        ]);

        // Demo behavior: clear cart after checkout
        $request->session()->forget('cart');

        return redirect()->route('qr.page', ['md5' => $order->md5]);
    }

    public function qrPage(string $md5)
    {
        $order = Order::where('md5', $md5)->firstOrFail();
        return view('pay.qr', ['order' => $order]);
    }

    public function check(Request $request, BakongApiService $bakong, TelegramService $telegram)
    {
        $md5 = trim((string)$request->input('md5', ''));

        Log::info('QR CHECK REQUEST', [
            'received_md5' => $md5
        ]);

        if ($md5 === '') return response()->json(['error' => 'md5 is required'], 400);

        $order = Order::where('md5', $md5)->first();
        if (!$order) return response()->json(['paid' => false, 'error' => 'unknown_md5'], 404);

        if ($order->status === 'PAID') {
            return response()->json([
                'paid' => true,
                'order' => $order,
                'paidData' => $order->paid_data_json ? json_decode($order->paid_data_json, true) : null,
            ]);
        }

        if ($order->expires_at && now()->greaterThan($order->expires_at)) {
            $order->status = 'EXPIRED';
            $order->save();
            return response()->json(['paid' => false, 'expired' => true, 'order' => $order], 410);
        }

        $resp = $bakong->checkTransactionByMd5($md5);
        if (!$resp['ok']) {
            return response()->json([
                'paid' => false,
                'error' => $resp['body'] ?? 'Bakong request failed',
            ], $resp['status'] ?? 500);
        }

        $body = $resp['body'] ?? [];
        $paid = $bakong->isPaid($body);

        if ($paid) {
            $order->status = 'PAID';
            $order->paid_at = now();
            $order->paid_data_json = json_encode($body['data'] ?? $body, JSON_UNESCAPED_UNICODE);
            $order->save();

            // Telegram notify once
            if (!$order->telegram_notified_at) {
                $paidData = $order->paid_data_json ? json_decode($order->paid_data_json, true) : [];
                $payer = $paidData['fromAccount'] ?? $paidData['sender'] ?? $paidData['payer'] ?? $paidData['payerAccount'] ?? '(unknown)';
                $time = $paidData['transactionDate'] ?? $paidData['timestamp'] ?? now()->toISOString();

                $telegram->sendOrderPaid([
                    'items' => json_decode($order->items_json, true) ?: [],
                    'total_khr' => $order->total_khr,
                ], [
                    'payer' => is_string($payer) ? $payer : json_encode($payer),
                    'time' => is_string($time) ? $time : json_encode($time),
                ]);

                $order->telegram_notified_at = now();
                $order->save();
            }
        }

        return response()->json([
            'paid' => $paid,
            'raw' => $body,
            'order' => $order,
        ]);
    }

    public function storeSuccess(string $md5)
    {
        $order = Order::where('md5', $md5)->firstOrFail();
        return view('pay.success', ['order' => $order]);
    }

    public function paidPage(string $md5)
    {
        // Alias to receipt
        return $this->storeSuccess($md5);
    }
}
