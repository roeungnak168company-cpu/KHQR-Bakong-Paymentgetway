<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BakongApiService
{
    public function checkTransactionByMd5(string $md5): array
    {
        $token = trim(env('BAKONG_TOKEN', ''), " \t\n\r\0\x0B\"'");
        if (!$token) {
            throw new \RuntimeException('Missing env BAKONG_TOKEN');
        }

        $resp = Http::timeout(15)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5', [
                'md5' => $md5,
            ]);

        if (!$resp->ok()) {
            return [
                'ok' => false,
                'status' => $resp->status(),
                'body' => $resp->json(),
            ];
        }

        return [
            'ok' => true,
            'status' => $resp->status(),
            'body' => $resp->json(),
        ];
    }

    public function isPaid(array $bakongResponse): bool
    {
        $rc = $bakongResponse['responseCode'] ?? null;
        return $rc === 0 || $rc === '0';
    }
}
