<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown when the SiPintu Gateway API call fails.
 *
 * Covers: connection errors, timeouts, invalid credentials (401/403),
 * and any non-2xx response from the Gateway.
 */
class SiPintuApiException extends RuntimeException
{
    public static function connectionError(): self
    {
        return new self('Gagal terhubung ke server SiPintu. Silakan coba lagi.');
    }

    public static function timeout(): self
    {
        return new self('Permintaan ke SiPintu melebihi batas waktu. Silakan coba lagi.');
    }

    public static function invalidCredentials(): self
    {
        return new self('Kredensial SiPintu tidak valid. Periksa konfigurasi aplikasi.');
    }

    public static function apiError(string $message): self
    {
        return new self($message);
    }
}
