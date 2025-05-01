<?php

namespace App\Enums;

/**
 * Action Type enum for logs
 */
class ActionType
{
    public const LOGIN_ACTION = 'Login';
    public const LOGOUT_ACTION = 'Logout';
    public const CREATE_ACTION = 'Create';
    public const READ_ACTION = 'Read';
    public const UPDATE_ACTION = 'Update';
    public const DELETE_ACTION = 'Delete';
    public const NOMINATION_ACTION = 'Nomination';
    public const ASSIGNMENT_ACTION = 'Assignment';
    public const EXPORT_ACTION = 'Export';
    public const PASSWORD_RESET_ACTION = 'Password_Reset';
    
    /**
     * Get all options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::LOGIN_ACTION,
            self::LOGOUT_ACTION,
            self::CREATE_ACTION,
            self::READ_ACTION,
            self::UPDATE_ACTION,
            self::DELETE_ACTION,
            self::NOMINATION_ACTION,
            self::ASSIGNMENT_ACTION,
            self::EXPORT_ACTION,
            self::PASSWORD_RESET_ACTION,
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
            self::LOGIN_ACTION => 'Login',
            self::LOGOUT_ACTION => 'Logout',
            self::CREATE_ACTION => 'Create',
            self::READ_ACTION => 'Read',
            self::UPDATE_ACTION => 'Update',
            self::DELETE_ACTION => 'Delete',
            self::NOMINATION_ACTION => 'Nomination',
            self::ASSIGNMENT_ACTION => 'Assignment',
            self::EXPORT_ACTION => 'Export',
            self::PASSWORD_RESET_ACTION => 'Password Reset',
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