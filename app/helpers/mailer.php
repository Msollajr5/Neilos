<?php
// ============================================================
// Neilos Partner Portal — SMTP Mailer Engine (Zero External Dependencies)
// Supports SSL (port 465), TLS/STARTTLS (port 587), and HTML MIME Emails
// ============================================================

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}

class PortalMailer {
    private static function getSmtpConfig(): array {
        return [
            'host'       => $_ENV['SMTP_HOST']       ?? getenv('SMTP_HOST')       ?: 'mail.neilosnetwork.co.tz',
            'port'       => (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT')       ?: 465),
            'user'       => $_ENV['SMTP_USER']       ?? getenv('SMTP_USER')       ?: 'mail@neilosnetwork.co.tz',
            'pass'       => $_ENV['SMTP_PASS']       ?? getenv('SMTP_PASS')       ?: 'Mails@2026',
            'secure'     => strtolower($_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: 'ssl'),
            'from_email' => $_ENV['SMTP_FROM']       ?? getenv('SMTP_FROM')       ?: 'mail@neilosnetwork.co.tz',
            'from_name'  => $_ENV['SMTP_FROM_NAME']  ?? getenv('SMTP_FROM_NAME')  ?: 'Neilos Partner Portal',
            'timeout'    => 15,
        ];
    }

    private static function logMail(string $level, string $message, array $context = []): void {
        $logDir = ROOT_DIR . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/mail.log';
        $timestamp = date('Y-m-d H:i:s');
        $ctxStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        @file_put_contents($logFile, "[{$timestamp}] [{$level}] {$message}{$ctxStr}\n", FILE_APPEND);
    }

    public static function send(string|array $to, string $subject, string $htmlBody, string $plainText = '', array $options = []): array {
        $config = self::getSmtpConfig();
        $recipients = is_array($to) ? $to : array_map('trim', explode(',', $to));
        $recipients = array_filter($recipients, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($recipients)) {
            self::logMail('ERROR', 'No valid recipient email addresses provided', ['raw_to' => $to]);
            return ['success' => false, 'message' => 'No valid recipient email addresses provided.'];
        }

        $host = $config['host'];
        $port = $config['port'];
        $user = $config['user'];
        $pass = $config['pass'];
        $fromEmail = $options['from_email'] ?? $config['from_email'];
        $fromName  = $options['from_name']  ?? $config['from_name'];

        // Candidate hosts with automatic fallback for DNS hiccups
        $candidateHosts = [$host];
        if (str_contains($host, 'neilosnetwork.co.tz') && $host !== '217.29.128.232') {
            $candidateHosts[] = '217.29.128.232';
        }

        $contextOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
                'peer_name'         => $host,
            ]
        ];
        $streamContext = stream_context_create($contextOptions);

        $socket = false;
        $lastErr = '';
        foreach ($candidateHosts as $tryHost) {
            $connectHost = $tryHost;
            $isSsl = ($config['secure'] === 'ssl' || $port === 465);
            if ($isSsl && !str_starts_with($connectHost, 'ssl://')) {
                $connectHost = 'ssl://' . $connectHost;
            }

            $socket = @stream_socket_client(
                "{$connectHost}:{$port}",
                $errno,
                $errstr,
                $config['timeout'],
                STREAM_CLIENT_CONNECT,
                $streamContext
            );

            if ($socket) {
                break;
            }
            $lastErr = "{$connectHost}:{$port} ($errno: $errstr)";
        }

        if (!$socket) {
            $errorMsg = "SMTP Connection failed: {$lastErr}";
            self::logMail('ERROR', $errorMsg, ['to' => $recipients, 'subject' => $subject]);
            return ['success' => false, 'message' => $errorMsg];
        }

        stream_set_timeout($socket, $config['timeout']);

        $readResponse = function() use ($socket) {
            $data = '';
            while ($str = fgets($socket, 515)) {
                $data .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $data;
        };

        $sendCommand = function(string $cmd, string $expectedCode) use ($socket, $readResponse) {
            fwrite($socket, $cmd . "\r\n");
            $res = $readResponse();
            $code = substr($res, 0, 3);
            if ($code !== $expectedCode) {
                throw new Exception("SMTP error on '{$cmd}': Expected {$expectedCode}, got {$res}");
            }
            return $res;
        };

        try {
            $greeting = $readResponse();
            if (substr($greeting, 0, 3) !== '220') {
                throw new Exception("Unexpected SMTP server greeting: {$greeting}");
            }

            $domain = 'neilosnetwork.co.tz';
            if (str_contains($fromEmail, '@')) {
                $parts = explode('@', $fromEmail);
                $domain = end($parts);
            }
            $ehloHost = 'mail.' . $domain;

            $sendCommand("EHLO " . $ehloHost, '250');

            // Handle STARTTLS on port 587 if not SSL
            if (!$isSsl && ($config['secure'] === 'tls' || $port === 587)) {
                $sendCommand("STARTTLS", '220');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("Failed to start TLS encryption");
                }
                $sendCommand("EHLO " . $ehloHost, '250');
            }

            // Authentication
            if (!empty($user)) {
                $sendCommand("AUTH LOGIN", '334');
                $sendCommand(base64_encode($user), '334');
                $sendCommand(base64_encode($pass), '235');
            }

            // Envelope Sender
            $sendCommand("MAIL FROM: <{$fromEmail}>", '250');

            // Envelope Recipients
            foreach ($recipients as $rcpt) {
                $sendCommand("RCPT TO: <{$rcpt}>", '250');
            }

            // DATA phase
            $sendCommand("DATA", '354');

            // Construct MIME message with Anti-Spam & Deliverability Headers
            $boundary = '=_neilos_mime_' . md5(uniqid(microtime(true), true));
            $messageId = sprintf('<%s.%s.%s@%s>', date('YmdHis'), substr(md5(uniqid(microtime(true), true)), 0, 12), bin2hex(random_bytes(4)), $domain);
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
            
            $headers = [];
            $headers[] = "Date: " . date('r');
            $headers[] = "Message-ID: {$messageId}";
            $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
            $headers[] = "Sender: <{$fromEmail}>";
            $headers[] = "Return-Path: <{$fromEmail}>";
            $headers[] = "Reply-To: {$encodedFromName} <{$fromEmail}>";
            $headers[] = "To: " . implode(', ', $recipients);
            $headers[] = "Subject: {$encodedSubject}";
            $headers[] = "Organization: Neilos Network Limited";
            $headers[] = "X-Mailer: Neilos Partner Portal Mailer v2.0";
            $headers[] = "Auto-Submitted: auto-generated";
            $headers[] = "X-Auto-Response-Suppress: All";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

            $plain = !empty($plainText) ? $plainText : strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

            $body  = implode("\r\n", $headers) . "\r\n\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($plain)) . "\r\n";

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";

            $body .= "--{$boundary}--\r\n";
            $body .= ".\r\n";

            fwrite($socket, $body);
            $res = $readResponse();
            if (substr($res, 0, 3) !== '250') {
                throw new Exception("DATA rejected by server: {$res}");
            }

            $sendCommand("QUIT", '221');
            @fclose($socket);

            self::logMail('INFO', 'Email sent successfully via SMTP', [
                'to'      => $recipients,
                'subject' => $subject,
                'host'    => $host,
                'port'    => $port
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully to ' . implode(', ', $recipients)
            ];

        } catch (Exception $e) {
            @fclose($socket);
            $errMsg = $e->getMessage();
            self::logMail('ERROR', "SMTP Delivery Failed: {$errMsg}", [
                'to'      => $recipients,
                'subject' => $subject,
                'host'    => $host,
                'port'    => $port
            ]);
            return [
                'success' => false,
                'message' => $errMsg
            ];
        }
    }
}

/**
 * Global helper to send portal emails
 */
function sendPortalEmail(string|array $to, string $subject, string $htmlBody, string $plainText = '', array $options = []): array {
    return PortalMailer::send($to, $subject, $htmlBody, $plainText, $options);
}
