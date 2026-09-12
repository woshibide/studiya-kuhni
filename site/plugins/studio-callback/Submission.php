<?php

namespace Studio\Callback;

use Throwable;

final class Submission
{
    public const UNAVAILABLE = 'Сейчас форма недоступна. Свяжитесь со студией по телефону.';
    public const SUCCESS = 'Заявка отправлена. Мы свяжемся с вами по указанному телефону.';

    public static function values(array $input): array
    {
        $values = [];
        foreach (['telephone', 'name', 'email', 'consent', 'website', 'source'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
        }
        return $values;
    }

    public static function available(array $config): bool
    {
        return ($config['environment'] ?? '') === 'production'
            && ($config['productionHost'] ?? false) === true
            && ($config['enabled'] ?? false) === true
            && filter_var($config['from'] ?? '', FILTER_VALIDATE_EMAIL) !== false
            && filter_var($config['to'] ?? '', FILTER_VALIDATE_EMAIL) !== false
            && ($config['transport']['type'] ?? '') === 'smtp'
            && trim($config['transport']['host'] ?? '') !== '';
    }

    public static function handle(
        array $input,
        bool $csrfValid,
        bool $available,
        callable $throttle,
        callable $deliver
    ): array {
        $values = self::values($input);
        if (!$csrfValid) {
            return self::result(403, 'Срок действия формы истёк. Обновите страницу и попробуйте снова.');
        }

        try {
            $retryAfter = $throttle();
        } catch (Throwable) {
            return self::result(503, self::UNAVAILABLE);
        }

        if ($retryAfter > 0) {
            return self::result(429, 'Слишком много попыток. Повторите через 15 минут или позвоните нам.', [], $retryAfter);
        }

        if ($values['website'] !== '') {
            return self::result(422, 'Не удалось отправить форму. Обновите страницу и попробуйте снова.');
        }

        $errors = [];
        $length = static fn (string $value): int => function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($length($values['name']) < 2 || $length($values['name']) > 100 || preg_match('/[\x00-\x1f\x7f]/u', $values['name'])) {
            $errors['name'] = 'Укажите имя: от 2 до 100 символов.';
        }

        $digits = preg_replace('/\D/', '', $values['telephone']);
        if (strlen($values['telephone']) > 50 || strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/^\+?[\d\s().-]+$/D', $values['telephone'])) {
            $errors['telephone'] = 'Укажите номер телефона: от 7 до 15 цифр.';
        }

        if ($values['email'] !== '' && (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL))) {
            $errors['email'] = 'Укажите адрес электронной почты, например name@example.com.';
        }
        if ($values['consent'] !== '1') {
            $errors['consent'] = 'Для отправки заявки нужно согласие на обработку персональных данных.';
        }
        if ($errors !== []) {
            return self::result(422, 'Проверьте отмеченные поля.', $errors);
        }
        if (!$available) {
            return self::result(503, self::UNAVAILABLE);
        }

        try {
            if ($deliver($values) !== true) {
                return self::result(503, self::UNAVAILABLE);
            }
        } catch (Throwable) {
            return self::result(503, self::UNAVAILABLE);
        }

        return self::result(200, self::SUCCESS);
    }

    private static function result(int $status, string $message, array $errors = [], int $retryAfter = 0): array
    {
        return ['ok' => $status === 200, 'status' => $status, 'message' => $message, 'errors' => $errors, 'retryAfter' => $retryAfter];
    }
}
