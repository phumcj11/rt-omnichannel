<?php
/**
 * แมป channel code → Font Awesome
 */
declare(strict_types=1);

namespace App\Helpers;

final class ChannelIcon
{
    public static function faClass(string $code): string
    {
        return match ($code) {
            'facebook_messenger' => 'fa-brands fa-facebook-messenger text-blue-600',
            'instagram' => 'fa-brands fa-instagram text-pink-600',
            'line_oa' => 'fa-brands fa-line text-emerald-600',
            'whatsapp' => 'fa-brands fa-whatsapp text-green-600',
            'web_chat' => 'fa-solid fa-globe text-slate-600',
            'tiktok_lead' => 'fa-brands fa-tiktok text-slate-900',
            default => 'fa-solid fa-comments text-slate-500',
        };
    }
}
