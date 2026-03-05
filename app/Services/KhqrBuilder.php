<?php

namespace App\Services;

class KhqrBuilder
{
    private function tlv(string $id, string $value): string
    {
        $len = str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $len . $value;
    }

    private function sanitize(?string $s): string
    {
        $s = (string)($s ?? '');
        $s = preg_replace("/[\r\n]/", ' ', $s);
        return trim($s);
    }

    // CRC16/CCITT-FALSE (poly 0x1021, init 0xFFFF)
    private function crc16ccittFalse(string $input): string
    {
        $crc = 0xFFFF;
        $bytes = array_values(unpack('C*', $input));

        foreach ($bytes as $b) {
            $crc ^= ($b << 8);
            for ($i = 0; $i < 8; $i++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Build Individual KHQR string (no tag 15 - matches official NBC KHQR SDK)
     * KHR only; amount must be integer.
     */
    public function buildKhqrString(array $input): string
    {
        $receiver = $this->sanitize($input['receiverBakongId'] ?? null);
        if ($receiver === '') throw new \InvalidArgumentException('receiverBakongId is required');

        $amount = $input['amountKhr'] ?? null;
        if (!is_numeric($amount) || (int)$amount <= 0) throw new \InvalidArgumentException('amountKhr must be > 0');
        if ((int)$amount != $amount) throw new \InvalidArgumentException('KHR amount must be an integer');

        $merchantName = $this->sanitize($input['merchantName'] ?? 'Merchant');
        $merchantCity = $this->sanitize($input['merchantCity'] ?? 'Phnom Penh');

        $createdMs = (string)($input['createdAtMs'] ?? '');
        $expiredMs = (string)($input['expiresAtMs'] ?? '');
        if (!preg_match('/^\d{13}$/', $createdMs)) throw new \InvalidArgumentException('createdAtMs must be 13 digits (ms epoch)');
        if (!preg_match('/^\d{13}$/', $expiredMs)) throw new \InvalidArgumentException('expiresAtMs must be 13 digits (ms epoch)');

        // Tag 00: Payload format indicator
        $p00 = $this->tlv('00', '01');

        // Tag 01: Point of initiation (12 = dynamic)
        $p01 = $this->tlv('01', '12');

        // Tag 29: Merchant account info (Individual KHQR - sub-tag 00 = bakong ID)
        $sub00 = '00' . str_pad((string)strlen($receiver), 2, '0', STR_PAD_LEFT) . $receiver;
        $p29 = $this->tlv('29', $sub00);

        // Tag 52: Merchant category code
        $p52 = $this->tlv('52', '5999');

        // Tag 53: Transaction currency (116 = KHR)
        $p53 = $this->tlv('53', '116');

        // Tag 54: Transaction amount
        $p54 = $this->tlv('54', (string)((int)$amount));

        // Tag 58: Country code
        $p58 = $this->tlv('58', 'KH');

        // Tag 59: Merchant name
        $p59 = $this->tlv('59', substr($merchantName, 0, 25));

        // Tag 60: Merchant city
        $p60 = $this->tlv('60', substr($merchantCity, 0, 15));

        // Tag 99: Timestamp (created + expiry)
        $s00 = '00' . '13' . $createdMs;
        $s01 = '01' . '13' . $expiredMs;
        $p99 = $this->tlv('99', $s00 . $s01);

        // Tag 63: CRC (always last)
        $withoutCrc = $p00 . $p01 . $p29 . $p52 . $p53 . $p54 . $p58 . $p59 . $p60 . $p99 . '6304';
        $crc = $this->crc16ccittFalse($withoutCrc);
        $p63 = '6304' . $crc;

        return $p00 . $p01 . $p29 . $p52 . $p53 . $p54 . $p58 . $p59 . $p60 . $p99 . $p63;
    }
}
