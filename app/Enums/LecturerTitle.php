<?php

namespace App\Enums;

/**
 * Lecturer Title enum
 */
class LecturerTitle
{
    public const DR = 'Dr';
    public const PROFESSOR_MADYA = 'Professor Madya';
    public const PROFESSOR = 'Professor';
    
    /**
     * Get all options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::DR,
            self::PROFESSOR_MADYA,
            self::PROFESSOR,
        ];
    }
    
    /**
     * Get all options as key-value pair for dropdown
     *
     * @return array
     */
    public static function forSelect(): array
    {
        return [
            self::DR => 'Dr',
            self::PROFESSOR_MADYA => 'Professor Madya',
            self::PROFESSOR => 'Professor',
        ];
    }
    
    /**
     * Check if a value is valid
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::all());
    }
}