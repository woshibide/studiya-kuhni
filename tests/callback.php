<?php

require_once dirname(__DIR__) . '/site/plugins/studio-callback/Submission.php';
require_once dirname(__DIR__) . '/site/plugins/studio-callback/Throttle.php';

use Studio\Callback\Submission;
use Studio\Callback\Throttle;

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$valid = ['name' => 'Тестовый клиент', 'telephone' => '+7 (999) 123-45-67', 'email' => 'client@example.com', 'consent' => '1', 'source' => 'fabrics/example'];
$sent = [];
$deliver = static function (array $data) use (&$sent): bool {
    $sent[] = $data;
    return true;
};
$allow = static fn () => 0;
$config = ['environment' => 'production', 'productionHost' => true, 'enabled' => true, 'from' => 'sender@example.com', 'to' => 'studio@example.com', 'transport' => ['type' => 'smtp', 'host' => 'smtp.example.com']];
$assert(Submission::available($config), 'Configured production can send');
foreach (['local', 'staging', 'unknown'] as $environment) {
    $assert(!Submission::available(array_replace($config, ['environment' => $environment])), 'Non-production cannot send');
}
foreach (['enabled' => false, 'productionHost' => false, 'from' => '', 'to' => 'invalid', 'transport' => []] as $key => $value) {
    $assert(!Submission::available(array_replace($config, [$key => $value])), 'Incomplete configuration cannot send');
}
$assert(!Submission::available([]), 'Unconfigured callback disabled');

$result = Submission::handle($valid, false, true, $allow, $deliver);
$assert($result['status'] === 403 && $sent === [], 'CSRF failure prevents delivery');
$result = Submission::handle($valid, true, true, static fn () => 899, $deliver);
$assert($result['status'] === 429 && $result['retryAfter'] === 899 && $sent === [], 'Throttle prevents delivery and supplies retry time');
$result = Submission::handle($valid + ['website' => 'bot'], true, true, $allow, $deliver);
$assert($result['status'] === 422 && $sent === [], 'Honeypot prevents delivery');
$result = Submission::handle(['telephone' => ['bad'], 'name' => '', 'email' => 'bad', 'consent' => []], true, true, $allow, $deliver);
$assert($result['status'] === 422 && count($result['errors']) === 4 && $sent === [], 'Malformed and absent fields produce field errors');
foreach (['name' => "Name\r\nBcc: attacker@example.com", 'telephone' => '123', 'email' => str_repeat('x', 255) . '@example.com', 'consent' => 'on'] as $field => $value) {
    $result = Submission::handle(array_replace($valid, [$field => $value]), true, true, $allow, $deliver);
    $assert($result['status'] === 422 && isset($result['errors'][$field]) && $sent === [], 'Invalid ' . $field . ' prevents delivery');
}
$result = Submission::handle($valid, true, false, $allow, $deliver);
$assert($result['status'] === 503 && !$result['ok'] && $sent === [], 'Disabled delivery never reports success');
$result = Submission::handle($valid, true, true, $allow, static function () { throw new RuntimeException('SMTP secret error'); });
$assert($result['status'] === 503 && !str_contains($result['message'], 'secret'), 'Delivery failures remain private and honest');
$result = Submission::handle($valid, true, true, static function () { throw new RuntimeException('Storage failure'); }, $deliver);
$assert($result['status'] === 503 && $sent === [], 'Throttle storage failure fails closed');
$result = Submission::handle($valid, true, true, $allow, static fn () => false);
$assert($result['status'] === 503 && !$result['ok'], 'False delivery result never reports success');
$result = Submission::handle(array_replace($valid, ['email' => '']), true, true, $allow, $deliver);
$assert($result['status'] === 200 && $result['ok'] && count($sent) === 1, 'Valid request with optional email delivers once');
$assert($sent[0]['source'] === $valid['source'], 'Source page retained for email resolution');

$directory = sys_get_temp_dir() . '/studio-callback-test-' . bin2hex(random_bytes(8));
try {
    $throttle = new Throttle($directory, 2, 60);
    $assert($throttle->attempt('192.0.2.1', 100) === 0, 'First attempt allowed');
    $assert($throttle->attempt('192.0.2.1', 110) === 0, 'Second attempt allowed');
    $assert($throttle->attempt('192.0.2.1', 120) === 40, 'Third attempt blocked for remaining window');
    $assert($throttle->attempt('192.0.2.2', 120) === 0, 'Different client independent');
    $assert(!str_contains(file_get_contents($directory . '/attempts.json'), '192.0.2'), 'Throttle does not retain raw IP');
    $assert($throttle->attempt('192.0.2.1', 160) === 0, 'Expired attempt frees allowance');
    $assert($throttle->attempt('192.0.2.1', 221) === 0, 'Expired records cleaned');
    $state = json_decode(file_get_contents($directory . '/attempts.json'), true);
    $assert(count($state['attempts']) === 1, 'Inactive clients removed');
} finally {
    if (is_file($directory . '/attempts.json')) unlink($directory . '/attempts.json');
    if (is_dir($directory)) rmdir($directory);
}

echo "Callback: {$checks} checks passed. No email sent.\n";
