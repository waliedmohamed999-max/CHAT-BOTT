<?php

declare(strict_types=1);

namespace MarketingCenter\Support;

final class Validator
{
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = 'required';
            }
        }
        return $errors;
    }

    public static function phone(string $value): string
    {
        $phone = preg_replace('/[^\d+]/', '', $value) ?: '';
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        if (!preg_match('/^\d{8,18}$/', $phone)) {
            throw new \InvalidArgumentException('invalid_phone_number');
        }
        return $phone;
    }

    public static function enum(string $value, array $allowed, string $error = 'invalid_value'): string
    {
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException($error);
        }
        return $value;
    }
}
