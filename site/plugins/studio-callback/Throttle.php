<?php

namespace Studio\Callback;

use RuntimeException;

final class Throttle
{
    public function __construct(private string $directory, private int $limit = 5, private int $window = 900)
    {
    }

    public function attempt(string $address, ?int $now = null): int
    {
        $now ??= time();
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Callback throttle storage unavailable.');
        }
        $handle = @fopen($this->directory . '/attempts.json', 'c+');
        if (!$handle || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new RuntimeException('Callback throttle lock unavailable.');
        }

        try {
            $raw = stream_get_contents($handle);
            $state = $raw !== '' ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : [];
            $state['salt'] ??= bin2hex(random_bytes(32));
            $attempts = $state['attempts'] ?? [];
            foreach ($attempts as $key => $timestamps) {
                $attempts[$key] = array_values(array_filter($timestamps, fn ($stamp) => $stamp > $now - $this->window));
                if ($attempts[$key] === []) {
                    unset($attempts[$key]);
                }
            }
            // Trust the peer address only; forwarded headers require a separately configured proxy boundary.
            $key = hash_hmac('sha256', $address, $state['salt']);
            $timestamps = $attempts[$key] ?? [];
            $retryAfter = count($timestamps) >= $this->limit ? max(1, $timestamps[0] + $this->window - $now) : 0;
            if ($retryAfter === 0) {
                $timestamps[] = $now;
                $attempts[$key] = $timestamps;
            }
            $encoded = json_encode(['salt' => $state['salt'], 'attempts' => $attempts], JSON_THROW_ON_ERROR);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $encoded) !== strlen($encoded) || !fflush($handle)) {
                throw new RuntimeException('Callback throttle write failed.');
            }
            return $retryAfter;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
