<?php

use Kirby\Cms\App;
use Kirby\Http\Response;
use Studio\Callback\Submission;
use Studio\Callback\Throttle;
use Studio\Callback\Store;
use Studio\Callback\Inbox;

require_once __DIR__ . '/Submission.php';
require_once __DIR__ . '/Throttle.php';
require_once __DIR__ . '/Store.php';
require_once __DIR__ . '/Inbox.php';

function studio_callback_config(): array
{
    return [
        'environment' => option('studio.environment', 'local'),
        'productionHost' => function_exists('studio_indexable') && studio_indexable(),
        'enabled' => option('studio.callback.enabled', filter_var(getenv('STUDIO_CALLBACK_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN)),
        'storage' => Store::directory(kirby()),
        'from' => option('studio.callback.from', getenv('STUDIO_CALLBACK_FROM') ?: ''),
        'to' => option('studio.callback.to', getenv('STUDIO_CALLBACK_TO') ?: ''),
        'transport' => option('studio.callback.transport', [
            'type' => 'smtp',
            'host' => getenv('STUDIO_SMTP_HOST') ?: '',
            'port' => (int)(getenv('STUDIO_SMTP_PORT') ?: 587),
            'security' => getenv('STUDIO_SMTP_SECURITY') ?: 'tls',
            'auth' => true,
            'username' => getenv('STUDIO_SMTP_USERNAME') ?: '',
            'password' => getenv('STUDIO_SMTP_PASSWORD') ?: '',
        ]),
    ];
}

App::plugin('studio/callback', [
    'permissions' => ['manage' => false],
    'areas' => ['studio-callback' => static fn (App $kirby) => Inbox::area($kirby)],
    'routes' => [
        [
            'pattern' => 'callback',
            'method' => 'POST',
            'action' => function () {
                $kirby = kirby();
                $input = $kirby->request()->body()->toArray();
                $config = studio_callback_config();
                $token = is_string($input['csrf'] ?? null) ? $input['csrf'] : '';
                $throttle = new Throttle($kirby->root('cache') . '/studio-callback');
                $result = Submission::handle(
                    $input,
                    $kirby->csrf($token),
                    Submission::available($config),
                    static fn () => $throttle->attempt((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')),
                    static function (array $values) use ($kirby, $config): bool {
                        $source = strlen($values['source']) <= 250 ? $kirby->page($values['source']) : null;
                        if (!$source || !$source->studioPubliclyVisible()) {
                            $source = $kirby->site()->homePage();
                        }
                        $store = Store::for($kirby);
                        $id = $store->create($values, $source->title()->value(), $source->url());
                        $body = implode("\n", [
                            'Заявка на обратный звонок',
                            '',
                            'Имя: ' . $values['name'],
                            'Телефон: ' . $values['telephone'],
                            'Электронная почта: ' . ($values['email'] ?: 'не указана'),
                            'Страница: ' . $source->title()->value(),
                            'Адрес страницы: ' . $source->url(),
                            'Согласие на обработку персональных данных: получено',
                        ]);
                        $sent = false;
                        try {
                            $email = $kirby->email([
                                'from' => $config['from'],
                                'to' => $config['to'],
                                'replyTo' => $values['email'] ?: null,
                                'subject' => 'Заявка на обратный звонок',
                                'body' => $body,
                                'transport' => $config['transport'],
                                'beforeSend' => function ($mailer) {
                                    $mailer->Timeout = 15;
                                    return $mailer;
                                },
                            ]);
                            $sent = $email->isSent();
                        } catch (\Throwable) {
                            // The durable inbox already accepted the request.
                        }
                        try {
                            $store->emailStatus($id, $sent);
                        } catch (\Throwable) {
                            // Keep pending if delivery status cannot be recorded.
                        }
                        return true;
                    }
                );

                $headers = ['Cache-Control' => 'no-store, private', 'X-Robots-Tag' => 'noindex, nofollow'];
                if ($result['retryAfter'] > 0) {
                    $headers['Retry-After'] = (string)$result['retryAfter'];
                }
                if (str_contains((string)$kirby->request()->header('Accept'), 'application/json')) {
                    return Response::json($result, $result['status'], false, $headers);
                }

                $values = Submission::values($input);
                unset($values['website']);
                $kirby->session()->set('studio.callback.flash', [
                    'result' => $result,
                    'values' => $result['ok'] ? [] : array_map(static fn ($value) => mb_substr($value, 0, 254), $values),
                    'expires' => time() + 600,
                ]);
                return new Response([
                    'code' => 303,
                    'headers' => $headers + ['Location' => url('contacts') . '#callback-form'],
                ]);
            },
        ],
    ],
]);
