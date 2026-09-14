<?php

namespace App\Support;

/**
 * Monta links mailto seguros para endereços de e-mail exibidos na interface.
 */
final class MailtoLink
{
    public static function normalize(?string $address): ?string
    {
        $address = trim((string) $address);

        if ($address === '' || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $address;
    }

    public static function href(?string $address): ?string
    {
        $email = self::normalize($address);

        return $email === null ? null : 'mailto:'.$email;
    }
}
