<?php

namespace App\Helpers;

use App\Models\User;

class PermissionHelper
{
    /**
     * Get all user permissions
     */
    public static function getUserPermissions(User $user): array
    {
        $role = $user->default_role;
        
        return [
            'role' => $role,
            'is_client' => self::isClient($user),
            'is_admin' => self::isAdmin($user),
            'can_readonly' => self::canReadOnly($user),
            'can_readwrite' => self::canReadWrite($user),
            'can_write' => self::canWrite($user),
        ];
    }
    
    /**
     * Check if user is a client
     */
    public static function isClient(User $user): bool
    {
        return $user->default_role === 'CLI' || empty($user->default_role);
    }
    
    /**
     * Check if user is not a client
     */
    public static function isNotClient(User $user): bool
    {
        return $user->default_role !== 'CLI' && !empty($user->default_role);
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin(User $user): bool
    {
        return $user->default_role === 'DBA';
    }
    
    /**
     * Check if user has read/write permissions
     */
    public static function canReadWrite(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW']);
    }
    
    /**
     * Check if user has read-only permissions
     */
    public static function canReadOnly(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW', 'DBRO']);
    }
    
    /**
     * Check if user can write (not a client)
     */
    public static function canWrite(User $user): bool
    {
        return $user->default_role !== 'CLI' && !empty($user->default_role);
    }
}