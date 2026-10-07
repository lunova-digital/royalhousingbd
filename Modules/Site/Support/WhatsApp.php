<?php

namespace Modules\Site\Support;

class WhatsApp
{
    /**
     * Build a wa.me click-to-chat link. Spaces are %20 (not +) because
     * WhatsApp shows a literal plus otherwise.
     */
    public static function link(?string $number, string $text = ''): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);
        $link = 'https://wa.me/'.$digits;
        if ($text !== '') {
            $link .= '?text='.rawurlencode($text);
        }

        return $link;
    }
}
