<?php

namespace App\Support;

final class UnifcoContact
{
    public const WHATSAPP_DISPLAY = '0599402090';
    public const WHATSAPP_E164 = '966599402090';
    public const EMAIL = 'info@unifco.com';

    public static function whatsappUrl(?string $message = null): string
    {
        $url = 'https://wa.me/'.self::WHATSAPP_E164;
        if ($message !== null && trim($message) !== '') {
            $url .= '?text='.rawurlencode($message);
        }
        return $url;
    }

    public static function mailto(): string
    {
        return 'mailto:'.self::EMAIL;
    }
}
