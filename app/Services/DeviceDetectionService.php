<?php

namespace App\Services;

class DeviceDetectionService
{
    public function detectPlatform(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            return 'ios';
        }

        if (str_contains($ua, 'android')) {
            return 'android';
        }

        if (
            str_contains($ua, 'mozilla') ||
            str_contains($ua, 'chrome') ||
            str_contains($ua, 'safari') ||
            str_contains($ua, 'firefox') ||
            str_contains($ua, 'edge')
        ) {
            return 'web';
        }

        return 'unknown';
    }

    public function isIos(string $userAgent): bool
    {
        return $this->detectPlatform($userAgent) === 'ios';
    }

    public function isAndroid(string $userAgent): bool
    {
        return $this->detectPlatform($userAgent) === 'android';
    }
}
