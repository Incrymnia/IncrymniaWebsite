<?php
/**
 * Minimal SMTP client (no Composer needed). Built for Gmail but works with
 * any SMTP server that supports AUTH LOGIN over SSL (465) or STARTTLS (587).
 */

declare(strict_types=1);

final class SmtpMailer
{
    /** @var resource|null */
    private $socket = null;

    /** Last error message (empty when the last send worked). */
    public string $error = '';

    /** Conversation transcript with credentials hidden. */
    public array $transcript = [];

    public function __construct(private array $cfg)
    {
    }

    public function send(
        string $fromEmail,
        string $fromName,
        string $to,
        string $replyToEmail,
        string $replyToName,
        string $subject,
        string $body
    ): bool {
        $this->error      = '';
        $this->transcript = [];

        try {
            $this->connect();
            $this->authenticate();

            $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', [354]);

            $message = $this->buildMessage($fromEmail, $fromName, $to, $replyToEmail, $replyToName, $subject, $body);
            $this->write($message . "\r\n.");
            $this->expect($this->read(), [250]);

            $this->command('QUIT', [221], true);
            return true;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        } finally {
            $this->close();
        }
    }

    /* ------------------------------------------------------------------ */

    private function connect(): void
    {
        $host       = (string) $this->cfg['host'];
        $port       = (int) $this->cfg['port'];
        $encryption = (string) ($this->cfg['encryption'] ?? 'ssl');
        $timeout    = (int) ($this->cfg['timeout'] ?? 12);

        $context = stream_context_create(['ssl' => [
            'verify_peer'       => (bool) ($this->cfg['verify_peer'] ?? true),
            'verify_peer_name'  => (bool) ($this->cfg['verify_peer'] ?? true),
            'peer_name'         => $host,
            'allow_self_signed' => false,
        ]]);

        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $socket = @stream_socket_client(
            "{$scheme}://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            $hint = '';
            if (stripos($errstr, 'certificate') !== false || stripos($errstr, 'SSL') !== false) {
                $hint = ' (PHP could not verify the certificate. On Windows, set openssl.cafile in php.ini to a CA bundle such as cacert.pem.)';
            }
            throw new RuntimeException("Could not connect to {$host}:{$port}: {$errstr} [{$errno}]{$hint}");
        }

        stream_set_timeout($socket, $timeout);
        $this->socket = $socket;

        $this->expect($this->read(), [220]);
        $this->command('EHLO ' . $this->localName(), [250]);

        if ($encryption === 'tls') {
            $this->command('STARTTLS', [220]);
            $ok = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($ok !== true) {
                throw new RuntimeException('STARTTLS handshake failed. Check that the PHP openssl extension is enabled.');
            }
            $this->command('EHLO ' . $this->localName(), [250]);
        }
    }

    private function authenticate(): void
    {
        $user = (string) $this->cfg['username'];
        $pass = (string) preg_replace('/\s+/', '', (string) $this->cfg['password']);

        $this->command('AUTH LOGIN', [334]);
        $this->command(base64_encode($user), [334], false, '[username hidden]');
        try {
            $this->command(base64_encode($pass), [235], false, '[password hidden]');
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                'Gmail rejected the login. Use a 16-character App Password (not your normal Gmail password) '
                . 'and make sure 2-Step Verification is on. Server said: ' . $e->getMessage()
            );
        }
    }

    private function buildMessage(
        string $fromEmail,
        string $fromName,
        string $to,
        string $replyToEmail,
        string $replyToName,
        string $subject,
        string $body
    ): string {
        $domain = substr(strrchr($fromEmail, '@') ?: '@localhost', 1);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->address($fromName, $fromEmail),
            'To: <' . $to . '>',
            'Reply-To: ' . $this->address($replyToName, $replyToEmail),
            'Subject: ' . $this->encodeHeader($subject, 9),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'X-Mailer: Emberforge SMTP',
        ];

        $encodedBody = chunk_split(base64_encode($body), 76, "\r\n");

        return implode("\r\n", $headers) . "\r\n\r\n" . rtrim($encodedBody);
    }

    private function address(string $name, string $email): string
    {
        $name = trim(str_replace(['"', '<', '>', '\\'], '', $name));
        return $name === '' ? '<' . $email . '>' : $this->encodeHeader($name, 6) . ' <' . $email . '>';
    }

    private function encodeHeader(string $text, int $offset): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $text)) {
            return $text;
        }
        $encoded = mb_encode_mimeheader($text, 'UTF-8', 'B', "\r\n ", $offset);
        return $encoded;
    }

    private function localName(): string
    {
        $name = $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost';
        return preg_match('/^[A-Za-z0-9.\-]+$/', (string) $name) ? (string) $name : 'localhost';
    }

    /* ---- low level ---------------------------------------------------- */

    private function command(string $line, array $okCodes, bool $lenient = false, ?string $logAs = null): void
    {
        $this->write($line, $logAs);
        try {
            $this->expect($this->read(), $okCodes);
        } catch (RuntimeException $e) {
            if (!$lenient) {
                throw $e;
            }
        }
    }

    private function write(string $line, ?string $logAs = null): void
    {
        $this->transcript[] = 'C: ' . ($logAs ?? (strlen($line) > 200 ? substr($line, 0, 200) . '...' : $line));
        $written = @fwrite($this->socket, $line . "\r\n");
        if ($written === false) {
            throw new RuntimeException('Connection to the mail server was lost while sending.');
        }
    }

    /** @return array{0:int,1:string} */
    private function read(): array
    {
        $text = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $text .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        if ($text === '') {
            $meta = stream_get_meta_data($this->socket);
            throw new RuntimeException(!empty($meta['timed_out'])
                ? 'The mail server took too long to respond.'
                : 'The mail server closed the connection.');
        }

        $this->transcript[] = 'S: ' . trim(str_replace("\r\n", ' | ', $text));
        return [(int) substr($text, 0, 3), trim($text)];
    }

    /** @param array{0:int,1:string} $reply */
    private function expect(array $reply, array $okCodes): void
    {
        if (!in_array($reply[0], $okCodes, true)) {
            throw new RuntimeException($reply[1]);
        }
    }

    private function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }
}
