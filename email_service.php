<?php
declare(strict_types=1);

/**
 * GRAMPAY SMTP sender.
 * Settings are read from environment variables or .env in this directory.
 * Required: GRAMPAY_SMTP_USER and GRAMPAY_SMTP_PASS
 * Optional: GRAMPAY_SMTP_HOST, GRAMPAY_SMTP_PORT, GRAMPAY_SMTP_FROM
 */
function grampayEnv(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return trim((string)$value);
    }

    static $env = null;
    if ($env === null) {
        $env = [];
        $path = __DIR__ . '/.env';
        if (is_file($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                $k = trim(substr($line, 0, $pos));
                $v = trim(substr($line, $pos + 1));
                if ($v !== '' && (($v[0] ?? '') === '"') && substr($v, -1) === '"') {
                    $v = substr($v, 1, -1);
                }
                if ($v !== '' && (($v[0] ?? '') === "'") && substr($v, -1) === "'") {
                    $v = substr($v, 1, -1);
                }
                $env[$k] = $v;
            }
        }
    }

    return isset($env[$key]) && $env[$key] !== '' ? (string)$env[$key] : $default;
}

function smtpRead($socket): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $response;
}

function smtpExpect($socket, array $codes): bool
{
    $response = smtpRead($socket);
    $code = (int)substr(trim($response), 0, 3);
    return in_array($code, $codes, true);
}

function smtpCommand($socket, string $command, array $codes): bool
{
    fwrite($socket, $command . "\r\n");
    return smtpExpect($socket, $codes);
}

function sendGrampayEmail(string $to, string $subject, string $html): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    $host = grampayEnv('GRAMPAY_SMTP_HOST', 'mail.hosting.reg.ru');
    $port = (int)grampayEnv('GRAMPAY_SMTP_PORT', '587');
    $user = grampayEnv('GRAMPAY_SMTP_USER');
    $pass = grampayEnv('GRAMPAY_SMTP_PASS');
    $from = grampayEnv('GRAMPAY_SMTP_FROM', $user);
    $fromName = grampayEnv('GRAMPAY_SMTP_FROM_NAME', 'GRAMPAY');

    if ($user === '' || $pass === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('GRAMPAY SMTP: missing GRAMPAY_SMTP_USER / GRAMPAY_SMTP_PASS / valid GRAMPAY_SMTP_FROM');
        return false;
    }

    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($host, $port, $errno, $errstr, 12);
    if (!$socket) {
        error_log('GRAMPAY SMTP connect failed: ' . $errstr . ' (' . $errno . ')');
        return false;
    }
    stream_set_timeout($socket, 12);

    try {
        if (!smtpExpect($socket, [220])) throw new RuntimeException('SMTP greeting failed');

        $localHost = preg_replace('/[^a-zA-Z0-9.\-]/', '', (string)($_SERVER['SERVER_NAME'] ?? 'grampay.net')) ?: 'grampay.net';
        if (!smtpCommand($socket, 'EHLO ' . $localHost, [250])) throw new RuntimeException('EHLO failed');

        if (!smtpCommand($socket, 'STARTTLS', [220])) throw new RuntimeException('STARTTLS failed');
        $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($crypto !== true) throw new RuntimeException('TLS negotiation failed');

        if (!smtpCommand($socket, 'EHLO ' . $localHost, [250])) throw new RuntimeException('EHLO after TLS failed');
        if (!smtpCommand($socket, 'AUTH LOGIN', [334])) throw new RuntimeException('AUTH LOGIN rejected');
        if (!smtpCommand($socket, base64_encode($user), [334])) throw new RuntimeException('SMTP username rejected');
        if (!smtpCommand($socket, base64_encode($pass), [235])) throw new RuntimeException('SMTP password rejected');

        if (!smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250])) throw new RuntimeException('MAIL FROM rejected');
        if (!smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251])) throw new RuntimeException('RCPT TO rejected');
        if (!smtpCommand($socket, 'DATA', [354])) throw new RuntimeException('DATA rejected');

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $body = preg_replace("/(\r\n|\r|\n)/", "\r\n", $html) ?? $html;
        $body = preg_replace('/\r\n\.\r\n/', "\r\n..\r\n", $body) ?? $body;
        $message = "From: {$encodedName} <{$from}>\r\n"
            . "To: <{$to}>\r\n"
            . "Subject: {$encodedSubject}\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "Date: " . date(DATE_RFC2822) . "\r\n"
            . "X-Mailer: GRAMPAY SMTP\r\n\r\n"
            . $body;

        fwrite($socket, $message . "\r\n.\r\n");
        if (!smtpExpect($socket, [250])) throw new RuntimeException('SMTP message was not accepted');

        @smtpCommand($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        error_log('GRAMPAY SMTP send failed: ' . $e->getMessage());
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return false;
    }
}

function sendVerificationCodeEmail(string $to, string $code, string $type = 'Email'): bool
{
    $subject = 'GRAMPAY — код подтверждения';
    $safeType = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<!doctype html><html><body style="font-family:Arial,sans-serif;background:#f5f7fb;padding:30px;color:#111827">'
        . '<div style="max-width:520px;margin:auto;background:white;border:1px solid #e5e7eb;border-radius:16px;padding:28px">'
        . '<h1 style="margin:0 0 10px">GRAMPAY</h1>'
        . '<p>Код для операции <b>' . $safeType . '</b>:</p>'
        . '<div style="font-size:34px;letter-spacing:8px;font-weight:800;padding:18px 0">' . $safeCode . '</div>'
        . '<p style="color:#6b7280">Код действует 10 минут. Не передавайте его другим людям.</p>'
        . '</div></body></html>';
    return sendGrampayEmail($to, $subject, $html);
}

function sendTwoFactorEmail(string $to, string $code, string $username = ''): bool
{
    $subject = 'GRAMPAY — код входа 2FA';
    $safeUser = htmlspecialchars($username !== '' ? $username : 'пользователь', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<!doctype html><html><body style="margin:0;background:#05070d;font-family:Arial,sans-serif;color:#f8fafc;padding:32px 14px">'
        . '<div style="max-width:560px;margin:auto;background:#0e1320;border:1px solid #26304a;border-radius:22px;padding:30px">'
        . '<div style="font-size:25px;font-weight:900">GRAMPAY</div>'
        . '<div style="margin-top:6px;color:#8793a8;font-size:12px">Подтверждение входа</div>'
        . '<div style="margin-top:26px;color:#cbd5e1;font-size:14px;line-height:1.6">Здравствуйте, <b style="color:#fff">' . $safeUser . '</b>.<br>Для входа в аккаунт используйте этот код 2FA:</div>'
        . '<div style="margin:26px 0;padding:20px;border:1px solid #39446a;border-radius:16px;text-align:center;background:#151c2d"><span style="font:900 38px/1 monospace;letter-spacing:9px;color:#a5b4fc">' . $safeCode . '</span></div>'
        . '<div style="color:#8793a8;font-size:11px;line-height:1.6">Код действует 10 минут. Если вы не входили в GRAMPAY, проигнорируйте письмо и смените пароль.</div>'
        . '</div></body></html>';
    return sendGrampayEmail($to, $subject, $html);
}
