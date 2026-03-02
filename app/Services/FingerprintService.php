<?php

namespace App\Services;

class FingerprintService
{
    /**
     * Compute a fingerprint hash from device/request attributes.
     * This MUST be identical on redirect-side and resolve-side.
     */
    public function compute(
        string $ip,
        string $userAgent,
        string $language,
        int $screenWidth = 0,
        int $screenHeight = 0
    ): string {
        $bucketedWidth = (int) (round($screenWidth / 10) * 10);
        $bucketedHeight = (int) (round($screenHeight / 10) * 10);

        $raw = implode('|', [
            strtolower(trim($ip)),
            strtolower(trim($userAgent)),
            strtolower(trim($language)),
            $bucketedWidth,
            $bucketedHeight,
        ]);

        return hash('sha256', $raw);
    }

    /**
     * Compute a loose fingerprint (ip + ua only) for fallback matching.
     */
    public function computeLoose(string $ip, string $userAgent): string
    {
        $raw = strtolower(trim($ip)) . '|' . strtolower(trim($userAgent));
        return hash('sha256', $raw);
    }
}
