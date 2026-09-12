<?php

namespace Studio;

final class Environment
{
    public static function name(?string $value): string
    {
        return in_array($value, ['local', 'staging', 'production'], true) ? $value : 'local';
    }

    public static function origin(?string $value): string
    {
        $url = rtrim(trim($value ?? ''), '/');
        $parts = parse_url($url);
        if (!$parts || !filter_var($url, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) ||
            isset($parts['fragment']) || !empty($parts['path'])) {
            return '';
        }
        return $url;
    }

    public static function indexable(string $environment, string $productionUrl, string $requestUrl): bool
    {
        $origin = self::origin($productionUrl);
        $host = strtolower((string)parse_url($requestUrl, PHP_URL_HOST));
        return $environment === 'production' && $origin !== '' &&
            !str_starts_with($host, 'design.') &&
            $host === strtolower((string)parse_url($origin, PHP_URL_HOST)) &&
            parse_url($requestUrl, PHP_URL_SCHEME) === 'https' &&
            (parse_url($requestUrl, PHP_URL_PORT) ?: 443) === (parse_url($origin, PHP_URL_PORT) ?: 443);
    }
}
