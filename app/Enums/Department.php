<?php

namespace App\Enums;

/**
 * Department enum
 */
class Department
{
    public const SEAT = 'SEAT';
    public const II = 'II';
    public const BIHG = 'BIHG';
    public const CAI = 'CAI';
    public const OTHER = 'Other';
    
    /**
     * Get all options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::SEAT,
            self::II,
            self::BIHG,
            self::CAI,
            self::OTHER,
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
            self::SEAT => 'SEAT',
            self::II => 'II',
            self::BIHG => 'BIHG',
            self::CAI => 'CAI',
            self::OTHER => 'Other',
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