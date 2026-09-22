<?php declare(strict_types=1);

function base32Encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = 0; $value = 0; $out = '';
    foreach (unpack('C*', $data) as $byte) {
        $value = ($value << 8) | $byte; $bits += 8;
        while ($bits >= 5) { $bits -= 5; $out .= $alphabet[($value >> $bits) & 31]; }
    }
    if ($bits > 0) $out .= $alphabet[($value << (5 - $bits)) & 31];
    return $out;
}

function base32Decode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data) ?? '');
    $bits = 0; $value = 0; $out = '';
    for ($i = 0, $len = strlen($data); $i < $len; $i++) {
        $pos = strpos($alphabet, $data[$i]); if ($pos === false) continue;
        $value = ($value << 5) | $pos; $bits += 5;
        if ($bits >= 8) { $bits -= 8; $out .= chr(($value >> $bits) & 255); }
    }
    return $out;
}

function generateTotpSecret(): string { return base32Encode(random_bytes(20)); }

function verifyTotp(string $secret, string $code, int $window = 1): bool {
    if (!preg_match('/^\d{6}$/', $code)) return false;
    $key = base32Decode($secret); if ($key === '') return false;
    $counter = intdiv(time(), 30);
    for ($offset = -$window; $offset <= $window; $offset++) {
        $binCounter = pack('N2', ($counter + $offset) >> 32, ($counter + $offset) & 0xffffffff);
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $pos = ord($hash[19]) & 15;
        $num = ((ord($hash[$pos]) & 127) << 24) |
               ((ord($hash[$pos + 1]) & 255) << 16) |
               ((ord($hash[$pos + 2]) & 255) << 8) |
               (ord($hash[$pos + 3]) & 255);
        if (hash_equals(str_pad((string)($num % 1000000), 6, '0', STR_PAD_LEFT), $code)) return true;
    }
    return false;
}
