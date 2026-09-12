<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

use Kirby\Cms\App;
use Kirby\Email\Email;
use Kirby\Filesystem\Dir;

$temporary = sys_get_temp_dir() . '/studio-callback-route-' . bin2hex(random_bytes(8));
mkdir($temporary . '/config', 0700, true);
$messages = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$token = bin2hex(random_bytes(32));
$input = ['csrf' => $token, 'name' => 'Тестовый клиент', 'telephone' => '+7 999 123-45-67', 'email' => 'client@example.com', 'consent' => '1', 'source' => 'contacts'];
$fakeDelivery = static function ($kirby, array $props) use (&$messages, $assert) {
    $messages[] = $props;
    // The base Email implementation marks sent without opening a transport.
    $email = new Email($props);
    $mailer = (object)['Timeout' => 300];
    $props['beforeSend']->call($email, $mailer);
    $assert($mailer->Timeout === 15, 'SMTP timeout hook can bind to Kirby email');
    return $email;
};

try {
    $kirby = new App([
        'roots' => [
            'index' => dirname(__DIR__),
            'config' => $temporary . '/config',
            'cache' => $temporary . '/cache',
            'sessions' => $temporary . '/sessions',
        ],
        'urls' => ['index' => 'https://studio.example.com'],
        'options' => [
            'debug' => true,
            'studio.environment' => 'production',
            'studio.productionUrl' => 'https://studio.example.com',
            'studio.callback.enabled' => true,
            'studio.callback.from' => 'sender@example.com',
            'studio.callback.to' => 'studio@example.com',
            'studio.callback.transport' => ['type' => 'smtp', 'host' => 'unused.invalid'],
        ],
        'components' => ['email' => $fakeDelivery],
        'request' => ['method' => 'POST', 'url' => 'https://studio.example.com/callback', 'body' => $input],
    ]);
    $kirby->session()->set('kirby.csrf', $token);
    $assert($kirby->component('email') === $fakeDelivery, 'Fake component installed before any endpoint call');
    $assert($kirby->csrf($token), 'Test session CSRF valid');
    $assert(Studio\Callback\Submission::available(studio_callback_config()), 'Test config enables fake production delivery');
    $response = $kirby->call('callback', 'POST');
    $assert($response->code() === 303, 'Native form redirects after delivery');
    $assert($response->header('Location') === 'https://studio.example.com/contacts#callback-form', 'Redirect is fixed same-site contact URL');
    $assert($response->header('Cache-Control') === 'no-store, private', 'Response cannot be cached');
    $assert(count($messages) === 1, 'Endpoint calls fake delivery exactly once: ' . json_encode($kirby->session()->get('studio.callback.flash')));
    $assert($messages[0]['from'] === 'sender@example.com' && $messages[0]['to'] === ['studio@example.com'], 'Configured mail identities preserved');
    $assert($messages[0]['replyTo'] === 'client@example.com', 'Visitor email used as reply-to only');
    $assert(str_contains($messages[0]['body'], 'https://studio.example.com/contacts'), 'Server resolves source URL');
    $flash = $kirby->session()->get('studio.callback.flash');
    $assert($flash['result']['ok'] === true && $flash['values'] === [], 'Success clears personal values');
    $assert($flash['expires'] > time(), 'Flash has bounded expiration');
} finally {
    if (isset($kirby)) $kirby->session()->destroy();
    Dir::remove($temporary);
}

echo "Callback route: {$checks} checks passed using fake delivery. No email sent.\n";
