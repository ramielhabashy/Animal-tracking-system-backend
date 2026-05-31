<?php

namespace App\Services;

class PaymentMethodService
{
    public static function getAvailableMethods(): array
    {
        $methods = config('payment.methods', []);
        return array_values(array_filter($methods, fn($m) => $m['enabled'] ?? true));
    }

    public static function getMethod(string $key): ?array
    {
        $method = config("payment.methods.{$key}");
        return ($method && ($method['enabled'] ?? true)) ? $method : null;
    }

    public static function getValidationRules(string $context = 'checkout'): string
    {
        $keys = config("payment.validation.{$context}", []);
        return 'in:' . implode(',', $keys);
    }

    public static function isValid(string $method, string $context = 'checkout'): bool
    {
        $keys = config("payment.validation.{$context}", []);
        return in_array($method, $keys);
    }

    public static function handlesRedirect(string $method): bool
    {
        $m = self::getMethod($method);
        return $m['requires_redirect'] ?? false;
    }
}
