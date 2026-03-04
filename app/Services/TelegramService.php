<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public function sendMessage(string $text): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        if (!$token || !$chatId) {
            return; // best-effort
        }

        Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
    }

    public function sendOrderPaid(array $order, array $payInfo = []): void
    {
        $items = $order['items'] ?? [];
        $itemsText = implode(', ', array_map(function ($i) {
            $name = $i['name'] ?? 'Item';
            $qty = $i['qty'] ?? 1;
            return $name . ' x' . $qty;
        }, $items));

        $total = (string)($order['total_khr'] ?? $order['totalKhr'] ?? '');
        $payer = (string)($payInfo['payer'] ?? '(unknown)');
        $time = (string)($payInfo['time'] ?? now()->toISOString());

        $msg =
            "🛍️ <b>NEW ORDER</b>\n" .
            "Items: " . ($itemsText !== '' ? $itemsText : '(none)') . "\n" .
            "Total: " . $total . " KHR\n" .
            "Paid by: " . $payer . "\n" .
            "Time: " . $time;

        $this->sendMessage($msg);
    }
}
