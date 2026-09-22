<?php
declare(strict_types=1);
// Uses only a loopback fake SMTP server and synthetic credentials; never delivers mail.
if (($argv[1] ?? '') === 'server') {
    $scenario = $argv[2];
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$server) exit(2);
    echo substr(strrchr(stream_socket_get_name($server, false), ':'), 1) . "\n";
    flush();
    $client = stream_socket_accept($server, 8);
    if (!$client) exit(3);
    stream_set_timeout($client, 8);
    $write = static function (string $s) use ($client): void { fwrite($client, $s . "\r\n"); };
    $read = static function () use ($client): string { return rtrim((string)fgets($client), "\r\n"); };
    $expect = static function (string $s) use ($read): void { if ($read() !== $s) throw new RuntimeException('Unexpected mock command'); };
    try {
        $write('220 mock SMTP');
        $expect('EHLO grampay.net');
        $write("250-mock\r\n250 AUTH LOGIN");
        if ($scenario === 'tls-rejected') {
            $expect('STARTTLS'); $write('454 TLS unavailable');
            // No AUTH downgrade is allowed after TLS rejection.
            if ($read() !== '') throw new RuntimeException('TLS downgraded');
        } else {
            $expect('AUTH LOGIN'); $write('334 VXNlcm5hbWU6');
            $expect(base64_encode('sender@example.test')); $write('334 UGFzc3dvcmQ6');
            $expect(base64_encode(' synthetic-password '));
            if ($scenario === 'auth-rejected') {
                $write('535 Authentication failed');
                if ($read() !== '') throw new RuntimeException('Continued after failed authentication');
            } else {
                $write('235 Authenticated');
                $expect('MAIL FROM:<sender@example.test>'); $write('250 Sender OK');
                $expect('RCPT TO:<recipient@example.test>');
                if ($scenario === 'recipient-rejected') {
                    $write('550 Recipient rejected');
                    if ($read() !== '') throw new RuntimeException('Continued after rejected recipient');
                } else {
                    $write('250 Recipient OK'); $expect('DATA'); $write('354 Send content');
                    $data = '';
                    while (($line = fgets($client)) !== false && $line !== ".\r\n") $data .= $line;
                    [$headers, $body] = explode("\r\n\r\n", $data, 2);
                    if (strpos($headers, 'Content-Transfer-Encoding: base64') === false) throw new RuntimeException('Encoding header missing');
                    if (base64_decode(preg_replace('/\s+/', '', $body), true) !== "<p>Код: 123456</p>\n.\n..dot\n") throw new RuntimeException('Message content corrupted');
                    $write($scenario === 'data-rejected' ? '554 Content rejected' : '250 Accepted');
                    // Close immediately after acceptance: send() must still return true.
                }
            }
        }
        fclose($client); fclose($server); exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . "\n"); exit(4);
    }
}
require_once dirname(__DIR__) . '/email_service.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
foreach (['accepted' => true, 'auth-rejected' => false, 'recipient-rejected' => false, 'data-rejected' => false, 'tls-rejected' => false] as $scenario => $expected) {
    $process = proc_open([PHP_BINARY, __FILE__, 'server', $scenario], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
    check(is_resource($process), 'Mock server start failed');
    stream_set_timeout($pipes[1], 8);
    $port = trim((string)fgets($pipes[1]));
    check(ctype_digit($port), 'Missing mock port');
    putenv('GRAMPAY_SMTP_HOST=127.0.0.1');
    putenv('GRAMPAY_SMTP_PORT=' . $port);
    putenv('GRAMPAY_SMTP_ENCRYPTION=' . ($scenario === 'tls-rejected' ? 'tls' : 'NOPE'));
    putenv('GRAMPAY_SMTP_USER=sender@example.test');
    putenv('GRAMPAY_SMTP_FROM=sender@example.test');
    putenv('GRAMPAY_SMTP_FROM_NAME=Test sender');
    putenv('GRAMPAY_SMTP_PASS= synthetic-password ');
    $ok = sendGrampayEmail('recipient@example.test', 'Тест SMTP', "<p>Код: 123456</p>\n.\n..dot\n");
    $error = stream_get_contents($pipes[2]);
    foreach ($pipes as $pipe) fclose($pipe);
    $exitCode = proc_close($process);
    check($exitCode === 0, $scenario . ': mock failed: ' . $error);
    check($ok === $expected, $scenario . ': unexpected result');
    echo 'PASS ' . $scenario . "\n";
}
check(!sendGrampayEmail("victim@example.test\r\nBcc:other@example.test", 'test', 'test'), 'Recipient header injection accepted');
echo "PASS recipient validation\n";
