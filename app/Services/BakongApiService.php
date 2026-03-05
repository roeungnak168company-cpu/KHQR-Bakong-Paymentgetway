<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BakongApiService
{
    // Switch between direct Bakong API or a proxy (for servers outside Cambodia)
    // Set BAKONG_USE_PROXY=true and BAKONG_PROXY_URL in Railway env to use proxy
    private function getApiUrl(): string
    {
        $useProxy = env('BAKONG_USE_PROXY', false);
        if ($useProxy) {
            return rtrim(env('BAKONG_PROXY_URL', ''), '/') . '/v1/check_transaction_by_md5';
        }
        return 'https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5';
    }

    public function checkTransactionByMd5(string $md5): array
    {
        $token = trim(env('BAKONG_TOKEN', ''), " \t\n\r\0\x0B\"'");
        if (!$token) {
            throw new \RuntimeException('Missing env BAKONG_TOKEN');
        }

        $url = $this->getApiUrl();

        Log::info('BAKONG API CALL', [
            'url'          => $url,
            'md5'          => $md5,
            'token_length' => strlen($token),
            'token_prefix' => substr($token, 0, 20) . '...',
        ]);

        $resp = Http::timeout(15)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])
            ->post($url, ['md5' => $md5]);

        Log::info('BAKONG API RESPONSE', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
        ]);

        if (!$resp->ok()) {
            return [
                'ok'     => false,
                'status' => $resp->status(),
                'body'   => $resp->json() ?? $resp->body(),
            ];
        }

        return [
            'ok'     => true,
            'status' => $resp->status(),
            'body'   => $resp->json(),
        ];
    }

    public function isPaid(array $bakongResponse): bool
    {
        $rc = $bakongResponse['responseCode'] ?? null;
        return $rc === 0 || $rc === '0';
    }
}
