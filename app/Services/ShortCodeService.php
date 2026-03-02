<?php

namespace App\Services;

use App\Models\Link;

class ShortCodeService
{
    private const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const LENGTH = 6;

    public function generate(): string
    {
        do {
            $code = $this->random();
        } while (Link::where('short_code', $code)->exists());

        return $code;
    }

    private function random(): string
    {
        $chars = self::CHARS;
        $result = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }
}
