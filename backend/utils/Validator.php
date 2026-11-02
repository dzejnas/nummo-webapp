<?php
require_once __DIR__ . '/ApiException.php';

// Small helpers used by services to validate request data.
// Each method returns the cleaned value or throws ApiException (HTTP 400).
class Validator
{
    public static function string(array $data, string $field, int $min = 1, int $max = 255): string
    {
        $value = trim((string) ($data[$field] ?? ''));
        if (mb_strlen($value) < $min || mb_strlen($value) > $max) {
            throw new ApiException("$field must be between $min and $max characters");
        }
        return $value;
    }

    public static function optionalString(array $data, string $field, int $max = 255): ?string
    {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            return null;
        }
        return self::string($data, $field, 1, $max);
    }

    public static function email(array $data, string $field = 'email'): string
    {
        $value = strtolower(self::string($data, $field, 3, 190));
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException("$field must be a valid email address");
        }
        return $value;
    }

    public static function id(array $data, string $field): int
    {
        $value = filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) {
            throw new ApiException("$field must be a positive whole number");
        }
        return $value;
    }

    // Money: greater than 0, at most 2 decimals
    public static function amount(array $data, string $field = 'amount'): float
    {
        $value = $data[$field] ?? null;
        if (!is_numeric($value) || $value <= 0 || $value > 1000000 || round($value, 2) != $value) {
            throw new ApiException("$field must be a positive number with at most 2 decimals");
        }
        return (float) $value;
    }

    public static function oneOf(array $data, string $field, array $allowed): string
    {
        $value = (string) ($data[$field] ?? '');
        if (!in_array($value, $allowed, true)) {
            throw new ApiException("$field must be one of: " . implode(', ', $allowed));
        }
        return $value;
    }

    // ISO currency code like EUR or USD
    public static function currency(array $data, string $field = 'currency'): string
    {
        $value = strtoupper(trim((string) ($data[$field] ?? '')));
        if (!preg_match('/^[A-Z]{3}$/', $value)) {
            throw new ApiException("$field must be a 3-letter currency code, e.g. EUR");
        }
        return $value;
    }
}
