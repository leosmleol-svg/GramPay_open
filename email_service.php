<?php
declare(strict_types=1);

/** SMTP configuration: environment > private PHP config > legacy .env > defaults.
 * Keep the password outside the public repository and document root.
 */
function grampayEnv(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') return (string)$value;
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $path = __DIR__ . '/.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $v = trim($v);
                if (strlen($v) >= 2 && in_array($v[0], ['"', "'"], true) && substr($v, -1) === $v[0]) {
                    $v = substr($v, 1, -1);
                }
                $settings[trim($k)] = $v;
            }
        }
        $privatePath = getenv('GRAMPAY_MAIL_CONFIG') ?: dirname(__DIR__) . '/grampay-mail.php';
        if (is_readable($privatePath)) {
            $private = require $privatePath;
            if (!is_array($private)) throw new RuntimeException('Private mail config must return an array');
            $settings = array_replace($settings, $private);
        }
    }
    return isset($settings[$key]) && $settings[$key] !== '' ? (string)$settings[$key] : $default;
}

function smtpWrite($socket, string $data): void
{
    $offset = 0;
    while ($offset < strlen($data)) {
        $written = @fwrite($socket, substr($data, $offset));
        if ($written === false || $written === 0) throw new RuntimeException('SMTP write failed or timed out');
        $offset += $written;
    }
}

function smtpRead($socket): string
{
    $response = '';
    $expected = null;
    for ($i = 0; $i < 100; $i++) {
        $line = fgets($socket, 515);
        if ($line === false) {
            $meta = stream_get_meta_data($socket);
            throw new RuntimeException(!empty($meta['timed_out']) ? 'SMTP response timed out' : 'SMTP connection closed');
        }
        if (!preg_match('/^(\d{3})([ -])/', $line, $m)) throw new RuntimeException('Malformed SMTP response');
        if ($expected !== null && $m[1] !== $expected) throw new RuntimeException('Inconsistent SMTP response');
        $expected = $m[1];
        $response .= $line;
        if ($m[2] === ' ') return $response;
    }
    throw new RuntimeException('SMTP response too long');
}

function smtpExpect($socket, array $codes): bool
{
    $response = smtpRead($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $codes, true)) {
        // Log codes only: server replies may contain addresses or authentication data.
        throw new RuntimeException('SMTP response ' . $code . '; expected ' . implode('/', $codes));
    }
    return true;
}

function smtpCommand($socket, string $command, array $codes): bool
{
    smtpWrite($socket, $command . "\r\n");
    return smtpExpect($socket, $codes);
}

function sendGrampayEmail(string $to, string $subject, string $html): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $socket = null;
    $stage = 'configuration';
    try {
        $host = trim(grampayEnv('GRAMPAY_SMTP_HOST', 'mail.hosting.reg.ru'));
        $port = (int)grampayEnv('GRAMPAY_SMTP_PORT', '587');
        $encryption = strtolower(trim(grampayEnv('GRAMPAY_SMTP_ENCRYPTION', 'none')));
        if (in_array($encryption, ['nope', 'no', 'off'], true)) $encryption = 'none';
        if ($encryption === 'starttls') $encryption = 'tls';
        $user = trim(grampayEnv('GRAMPAY_SMTP_USER', 'admin@grampay.net'));
        $pass = grampayEnv('GRAMPAY_SMTP_PASS');
        $from = trim(grampayEnv('GRAMPAY_SMTP_FROM', $user));
        $fromName = grampayEnv('GRAMPAY_SMTP_FROM_NAME', 'GRAMPAY');
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $host) || $port < 1 || $port > 65535) throw new RuntimeException('Invalid SMTP host or port');
        if (!in_array($encryption, ['none', 'tls', 'ssl'], true)) throw new RuntimeException('Encryption must be none, tls, or ssl');
        if ($user === '' || $pass === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Missing SMTP credentials or invalid From address');
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true, 'verify_peer_name' => true,
            'allow_self_signed' => false, 'peer_name' => $host,
        ]]);
        $stage = 'connect';
        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $context);
        if ($socket === false) throw new RuntimeException('Connection failed (code ' . $errno . ')');
        stream_set_timeout($socket, 12);
        $stage = 'greeting';
        smtpExpect($socket, [220]);
        $localHost = 'grampay.net';
        $stage = 'EHLO';
        smtpCommand($socket, 'EHLO ' . $localHost, [250]);
        if ($encryption === 'tls') {
            $stage = 'STARTTLS';
            smtpCommand($socket, 'STARTTLS', [220]);
            if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) throw new RuntimeException('TLS negotiation failed');
            smtpCommand($socket, 'EHLO ' . $localHost, [250]);
        }
        $stage = 'authentication';
        smtpCommand($socket, 'AUTH LOGIN', [334]);
        smtpCommand($socket, base64_encode($user), [334]);
        smtpCommand($socket, base64_encode($pass), [235]);
        $stage = 'sender';
        smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
        $stage = 'recipient';
        smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        $stage = 'DATA';
        smtpCommand($socket, 'DATA', [354]);
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        // Base64 preserves UTF-8 and prevents SMTP dot termination in HTML content.
        $body = chunk_split(base64_encode($html), 76, "\r\n");
        $message = "From: {$encodedName} <{$from}>\r\n"
            . "To: <{$to}>\r\nSubject: {$encodedSubject}\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . 'Date: ' . date(DATE_RFC2822) . "\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(16)) . "@grampay.net>\r\n"
            . "X-Mailer: GRAMPAY SMTP\r\n\r\n" . $body;
        smtpWrite($socket, $message . ".\r\n");
        $stage = 'message acceptance';
        smtpExpect($socket, [250]);
        // Acceptance is final. A missing QUIT reply must not cause duplicate mail.
        @fwrite($socket, "QUIT\r\n");
        return true;
    } catch (Throwable $e) {
        error_log('GRAMPAY SMTP [' . $stage . ']: ' . $e->getMessage());
        return false;
    } finally {
        if (is_resource($socket)) fclose($socket);
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

