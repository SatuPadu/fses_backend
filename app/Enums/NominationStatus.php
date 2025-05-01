<?php

namespace App\Enums;

/**
 * Nomination Status enum
 */
class NominationStatus
{
    public const PENDING = 'Pending';
    public const NOMINATED = 'Nominated';
    public const LOCKED = 'Locked';
    
    /**
     * Get all options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::NOMINATED,
            self::LOCKED,
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
            self::PENDING => 'Pending',
            self::NOMINATED => 'Nominated',
            self::LOCKED => 'Locked',
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