<?php

namespace Studio;

use Symfony\Component\Yaml\Yaml;
use Throwable;

final class Location
{
    public static function resolve(
        ?string $locator,
        mixed $legacyLat,
        mixed $legacyLng,
        mixed $legacyZoom,
        string $label,
        bool $locatorPresent = false
    ): ?array {
        $locator = trim($locator ?? '');
        if ($locator !== '') {
            try {
                $value = Yaml::parse($locator);
            } catch (Throwable) {
                return null;
            }
            if (!is_array($value)) return null;
            $lat = self::coordinate($value['lat'] ?? null, 90);
            $lng = self::coordinate($value['lon'] ?? null, 180);
            $zoom = self::zoom($value['zoom'] ?? null);
        } else {
            if ($locatorPresent) return null;
            $lat = self::coordinate($legacyLat, 90);
            $lng = self::coordinate($legacyLng, 180);
            $zoom = self::zoom($legacyZoom);
        }

        if ($lat === null || $lng === null) return null;

        return ['lat' => $lat, 'lng' => $lng, 'zoom' => $zoom, 'label' => trim($label)];
    }

    private static function coordinate(mixed $value, int $limit): ?float
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) return null;
        if (!is_numeric($value)) return null;
        $number = (float)$value;
        return is_finite($number) && abs($number) <= $limit ? $number : null;
    }

    private static function zoom(mixed $value): int
    {
        $number = self::coordinate($value, 19);
        return $number !== null && $number >= 1 && floor($number) === $number ? (int)$number : 13;
    }
}
