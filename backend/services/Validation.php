<?php
final class Validation {
    public static function str_required(array $data, string $key, int $min = 1, int $max = 255): string {
        if (!isset($data[$key])) throw new Exception("$key is required", 400);
        $v = trim((string)$data[$key]);
        if (strlen($v) < $min || strlen($v) > $max) throw new Exception("$key length must be $min..$max", 400);
        return $v;
    }

    public static function str_optional(array $data, string $key, ?int $min = null, ?int $max = 255): ?string {
        if (!isset($data[$key]) || $data[$key] === null) return null;
        $v = trim((string)$data[$key]);
        if ($min !== null && strlen($v) < $min) throw new Exception("$key length must be >= $min", 400);
        if ($max !== null && strlen($v) > $max) throw new Exception("$key length must be <= $max", 400);
        return $v === '' ? null : $v;
    }

    public static function int_required(array $data, string $key, int $min = 1): int {
        if (!isset($data[$key])) throw new Exception("$key is required", 400);
        $v = (int)$data[$key];
        if ($v < $min) throw new Exception("$key must be >= $min", 400);
        return $v;
    }

    public static function num_required(array $data, string $key, float $min = 0.0): float {
        if (!isset($data[$key])) throw new Exception("$key is required", 400);
        $v = (float)$data[$key];
        if ($v <= $min) throw new Exception("$key must be > $min", 400);
        return $v;
    }

    public static function email_required(array $data, string $key = 'email'): string {
        $email = self::str_required($data, $key, 3, 255);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid $key format", 400);
        return $email;
    }

    public static function in_enum(string $value, array $allowed, string $field): string {
        if (!in_array($value, $allowed, true)) throw new Exception("$field must be one of: " . implode(', ', $allowed), 400);
        return $value;
    }
}
