<?php

namespace App\Enums;

/**
 * User Role enum
 */
class UserRole
{
    /**
     * Office Assistant role
     * Responsible for administrative tasks and basic operations
     */
    public const OFFICE_ASSISTANT = 'OfficeAssistant';
    
    /**
     * Supervisor role
     * Responsible for supervising students and their research
     */
    public const SUPERVISOR = 'Supervisor';
    
    /**
     * Program Coordinator role
     * Responsible for coordinating academic programs
     */
    public const PROGRAM_COORDINATOR = 'ProgramCoordinator';
    
    /**
     * PGAM role (Postgraduate Academic Manager)
     * Responsible for overseeing postgraduate academic matters
     */
    public const PGAM = 'PGAM';
    
    /**
     * Get all role options as an array
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::OFFICE_ASSISTANT,
            self::SUPERVISOR,
            self::PROGRAM_COORDINATOR,
            self::PGAM,
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
            self::OFFICE_ASSISTANT => 'Office Assistant',
            self::SUPERVISOR => 'Supervisor',
            self::PROGRAM_COORDINATOR => 'Program Coordinator',
            self::PGAM => 'Postgraduate Academic Manager',
        ];
    }
    
    /**
     * Get role descriptions
     *
     * @return array
     */
    public static function descriptions(): array
    {
        return [
            self::OFFICE_ASSISTANT => 'Handles administrative tasks and basic operations',
            self::SUPERVISOR => 'Supervises students and their research progress',
            self::PROGRAM_COORDINATOR => 'Coordinates academic programs and curriculum',
            self::PGAM => 'Oversees all postgraduate academic matters',
        ];
    }
    
    /**
     * Get description for a specific role
     *
     * @param string $role
     * @return string|null
     */
    public static function getDescription(string $role): ?string
    {
        return self::descriptions()[$role] ?? null;
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
    
    /**
     * Get role hierarchy level (higher number = higher privilege)
     *
     * @param string $role
     * @return int
     */
    public static function getHierarchyLevel(string $role): int
    {
        $hierarchy = [
            self::OFFICE_ASSISTANT => 1,
            self::SUPERVISOR => 2,
            self::PROGRAM_COORDINATOR => 3,
            self::PGAM => 4,
        ];
        
        return $hierarchy[$role] ?? 0;
    }
    
    /**
     * Check if one role has higher privileges than another
     *
     * @param string $roleA
     * @param string $roleB
     * @return bool True if $roleA has higher privileges than $roleB
     */
    public static function hasHigherPrivilege(string $roleA, string $roleB): bool
    {
        return self::getHierarchyLevel($roleA) > self::getHierarchyLevel($roleB);
    }
}