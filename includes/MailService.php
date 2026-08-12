<?php
/**
 * Lightweight SMTP mail service.
 * Uses MAIL_* environment configuration and records delivery attempts in email_logs.
 */

class MailService
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromAddress;
    private $fromName;
    private $enabled;

    public function __construct()
    {
        $this->host = MAIL_HOST;
        $this->port = MAIL_PORT;
        $this->username = MAIL_USERNAME;
        $this->password = MAIL_PASSWORD;
        $this->fromAddress = MAIL_FROM_ADDRESS;
        $this->fromName = MAIL_FROM_NAME;
        $this->enabled = $this->host !== '' && $this->port > 0 && $this->fromAddress !== '';
    }

    public function isConfigured(): bool
    {
        return $this->enabled;
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = '', ?int $notificationId = null): array
    {
        if (!$this->isConfigured()) {
            $result = ['success' => false, 'error' => 'Mail service is not configured'];
            $this->logAttempt($notificationId, $toEmail, $subject, 'not_configured', $result['error']);
            return $result;
        }

        $textBody = $textBody !== '' ? $textBody : trim(strip_tags($htmlBody));

        $socket = null;

        try {
            $socket = $this->openConnection();
            $this->expect($socket, [220]);

            $this->command($socket, 'EHLO localhost');
            $this->expect($socket, [250]);

            if ($this->port === 587) {
                $this->command($socket, 'STARTTLS');
                $this->expect($socket, [220]);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Failed to enable TLS encryption');
                }

                $this->command($socket, 'EHLO localhost');
                $this->expect($socket, [250]);
            }

            if ($this->username !== '') {
                $this->command($socket, 'AUTH LOGIN');
                $this->expect($socket, [334]);
                $this->command($socket, base64_encode($this->username));
                $this->expect($socket, [334]);
                $this->command($socket, base64_encode($this->password));
                $this->expect($socket, [235]);
            }

            $this->command($socket, 'MAIL FROM:<' . $this->fromAddress . '>');
            $this->expect($socket, [250]);
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>');
            $this->expect($socket, [250, 251]);
            $this->command($socket, 'DATA');
            $this->expect($socket, [354]);

            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->formatAddress($this->fromAddress, $this->fromName),
                'To: ' . $this->formatAddress($toEmail, $toName),
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $payload = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
            $this->command($socket, $payload);
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT');
            @fclose($socket);

            $this->logAttempt($notificationId, $toEmail, $subject, 'sent', null);

            return ['success' => true];
        } catch (Throwable $e) {
            if (is_resource($socket)) {
                @fclose($socket);
            }
            $this->logAttempt($notificationId, $toEmail, $subject, 'failed', $e->getMessage());
            error_log('Mail send failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function renderDefaultTemplate(string $heading, array $lines, string $ctaUrl = '', string $ctaLabel = 'Open CRM'): string
    {
        $escapedLines = array_map(static function ($line) {
            return nl2br(htmlspecialchars((string) $line, ENT_QUOTES, 'UTF-8'));
        }, $lines);

        $button = '';
        if ($ctaUrl !== '') {
            $button = '<p style="margin-top:24px;"><a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#0f2942;color:#ffffff;padding:12px 18px;border-radius:6px;text-decoration:none;display:inline-block;">' . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8') . '</a></p>';
        }

        return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;padding:24px;color:#1f2937;">'
            . '<h2 style="color:#0f2942;margin-bottom:16px;">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<div style="line-height:1.6;font-size:14px;">'
            . '<p>' . implode('</p><p>', $escapedLines) . '</p>'
            . $button
            . '</div>'
            . '<p style="margin-top:32px;color:#6b7280;font-size:12px;">' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div>';
    }

    private function openConnection()
    {
        $transport = $this->port === 465 ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            20,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new RuntimeException('Failed to connect to SMTP server: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($socket, 20);

        return $socket;
    }

    private function command($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function expect($socket, array $codes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $status = (int) substr($response, 0, 3);
        if (!in_array($status, $codes, true)) {
            throw new RuntimeException('SMTP error [' . $status . ']: ' . trim($response));
        }

        return $response;
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeName = trim($name);
        if ($safeName === '') {
            return '<' . $email . '>';
        }
        return '=?UTF-8?B?' . base64_encode($safeName) . '?= <' . $email . '>';
    }

    private function logAttempt(?int $notificationId, string $recipient, string $subject, string $status, ?string $errorMessage): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('email_logs', [
                'notification_id' => $notificationId,
                'recipient_email' => $recipient,
                'subject' => $subject,
                'status' => $status,
                'error_message' => $errorMessage,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            error_log('Email log insert failed: ' . $e->getMessage());
        }
    }
}
