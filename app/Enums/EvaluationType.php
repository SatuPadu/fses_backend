<?php

namespace App\Enums;

/**
 * Evaluation Type enum
 */
class EvaluationType
{
    public const FIRST_EVALUATION = 'FirstEvaluation';
    public const RE_EVALUATION = 'ReEvaluation';
    
    /**
     * Get all options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::FIRST_EVALUATION,
            self::RE_EVALUATION,
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
            self::FIRST_EVALUATION => 'First Evaluation',
            self::RE_EVALUATION => 'Re-Evaluation',
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