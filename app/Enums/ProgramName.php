<?php

namespace App\Enums;
/**
 * Program Name enum
 */
class ProgramName
{
    public const PHD = 'PhD';
    public const MPHIL = 'MPhil';
    public const DSE = 'DSE';

    /**
     * Get all options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::PHD,
            self::MPHIL,
            self::DSE,
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
            self::PHD => 'PhD',
            self::MPHIL => 'MPhil',
            self::DSE => 'DSE',
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