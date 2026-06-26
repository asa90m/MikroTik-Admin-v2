<?php

/**
 * عميل مبسّط لبروتوكول MikroTik RouterOS API.
 * يتصل بالراوتر عبر المنفذ 8728 (أو 8729 لاتصال مشفّر عبر stream_socket_client مع ssl://)
 * الترميز يتبع توثيق MikroTik الرسمي لكلمات API ذات الطول المتغير.
 *
 * استخدام:
 *   $api = new RouterosAPI();
 *   if ($api->connect('192.168.88.1', 'admin', 'secret')) {
 *       $rows = $api->comm('/ip/hotspot/active/print');
 *   }
 */
class RouterosAPI
{
    public bool $connected = false;
    public string $errorStr = '';

    /** @var resource|null */
    private $socket = null;

    public function connect(string $host, string $user, string $pass, int $port = 8728, int $timeout = 5): bool
    {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$this->socket) {
            $this->errorStr = "تعذر الاتصال بـ {$host}:{$port} — {$errstr}";
            return false;
        }
        stream_set_timeout($this->socket, $timeout);

        // RouterOS الإصدار 6.43 فما فوق يدعم تسجيل دخول مباشر بجملة واحدة
        $this->write('/login', ['name' => $user, 'password' => $pass]);
        $reply = $this->readSentence();

        if (empty($reply) || $reply[0] === '!trap' || $reply[0] === '!fatal') {
            $this->errorStr = 'فشل تسجيل الدخول — تحقّق من اسم المستخدم وكلمة المرور والصلاحيات';
            $this->disconnect();
            return false;
        }

        $this->connected = true;
        return true;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    /** يرسل أمر API مع وسائطه كجملة واحدة منتهية بكلمة ذات طول صفر */
    public function write(string $command, array $params = []): void
    {
        $this->writeWord($command);
        foreach ($params as $key => $value) {
            $this->writeWord("={$key}={$value}");
        }
        fwrite($this->socket, chr(0));
    }

    /** ينفّذ أمراً ويجمع كل صفوف !re حتى تصل الاستجابة إلى !done */
    public function comm(string $command, array $params = []): array
    {
        if (!$this->connected && $command !== '/login') {
            $this->errorStr = 'غير متصل بالراوتر';
            return [];
        }

        $this->write($command, $params);
        $rows = [];

        while (true) {
            $sentence = $this->readSentence();
            if (empty($sentence)) {
                break;
            }
            $type = $sentence[0];

            if ($type === '!re') {
                $row = [];
                foreach (array_slice($sentence, 1) as $word) {
                    if (preg_match('/^=([^=]+)=(.*)$/s', $word, $m)) {
                        $row[$m[1]] = $m[2];
                    }
                }
                $rows[] = $row;
            } elseif ($type === '!trap' || $type === '!fatal') {
                $this->errorStr = implode(' ', array_slice($sentence, 1));
                break;
            } elseif ($type === '!done') {
                break;
            }
        }

        return $rows;
    }

    private function readSentence(): array
    {
        $words = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') {
                break;
            }
            $words[] = $word;
        }
        return $words;
    }

    private function writeWord(string $word): void
    {
        fwrite($this->socket, $this->encodeLength(strlen($word)) . $word);
    }

    private function readWord(): string
    {
        $length = $this->readLength();
        if ($length <= 0) {
            return '';
        }
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function encodeLength(int $len): string
    {
        if ($len < 0x80) {
            return chr($len);
        }
        if ($len < 0x4000) {
            $len |= 0x8000;
            return chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
        }
        if ($len < 0x200000) {
            $len |= 0xC00000;
            return chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
        }
        if ($len < 0x10000000) {
            $len |= 0xE0000000;
            return chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
        }
        return chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
    }

    private function readLength(): int
    {
        $byte = fread($this->socket, 1);
        if ($byte === false || $byte === '') {
            return 0;
        }
        $byte = ord($byte);

        if (($byte & 0x80) === 0) {
            return $byte;
        }
        if (($byte & 0xC0) === 0x80) {
            return (($byte & 0x3F) << 8) + ord(fread($this->socket, 1));
        }
        if (($byte & 0xE0) === 0xC0) {
            return (($byte & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }
        if (($byte & 0xF0) === 0xE0) {
            return (($byte & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }
        return (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
    }
}
