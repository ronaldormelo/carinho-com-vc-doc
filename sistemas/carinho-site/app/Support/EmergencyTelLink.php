<?php

namespace App\Support;

/**
 * Links tel: para os números de emergência públicos exibidos na interface.
 */
final class EmergencyTelLink
{
    public const NUMBERS = [
        '192' => 'SAMU',
        '193' => 'Bombeiros',
        '190' => 'Polícia',
        '188' => 'CVV',
    ];

    public static function normalize(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        return array_key_exists($digits, self::NUMBERS) ? $digits : null;
    }

    public static function href(?string $number): ?string
    {
        $normalized = self::normalize($number);

        return $normalized === null ? null : 'tel:'.$normalized;
    }

    public static function label(string $number): string
    {
        $normalized = self::normalize($number) ?? $number;

        return self::NUMBERS[$normalized] ?? $normalized;
    }

    /**
     * Escapa o texto e envolve 190/192/193/188 em âncoras tel:.
     */
    public static function linkify(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pattern = '/(?<!\\d)(190|192|193|188)(?!\\d)/';

        $linked = preg_replace_callback($pattern, static function (array $match): string {
            $number = $match[1];
            $name = self::NUMBERS[$number];

            return '<a href="tel:'.$number.'" class="emergency-tel-link" aria-label="Ligar para '.$name.', '.$number.'">'.$number.'</a>';
        }, $escaped);

        return $linked ?? $escaped;
    }
}
