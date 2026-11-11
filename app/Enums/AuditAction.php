<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Audit eylem kataloğu — docs/ADMIN.md §8. Eylem adları sabittir; yeni eylem önce ADMIN.md'ye eklenir.
 */
enum AuditAction: string
{
    case AdminLogin = 'auth.admin_login';
    case AdminLogout = 'auth.admin_logout';
    case AdminLoginFailed = 'auth.admin_login_failed';
    case UsersSuspended = 'users.suspended';
    case UsersUnsuspended = 'users.unsuspended';
    case UsersAnonymized = 'users.anonymized';
    case RolesAssigned = 'roles.assigned';
    case RolesRevoked = 'roles.revoked';
    case RolesExpired = 'roles.expired';
    case RolesCreated = 'roles.created';
    case RolesUpdated = 'roles.updated';
    case RolesDeleted = 'roles.deleted';
    case RolesPermissionsSynced = 'roles.permissions_synced';
    case SuperAdminAssigned = 'roles.super_admin_assigned';
    case SuperAdminRevoked = 'roles.super_admin_revoked';
    case SuperAdminBreakGlass = 'roles.super_admin_break_glass';
    case BranchesCreated = 'branches.created';
    case BranchesUpdated = 'branches.updated';
    case BranchesMemberAdded = 'branches.member_added';
    case BranchesMemberRemoved = 'branches.member_removed';
    case BranchesLeadChanged = 'branches.lead_changed';
    case StaffOffboarded = 'staff.offboarded';
    case MfaReset = 'security.mfa_reset';
    case ContentPublished = 'content.published';
    case ContentUnpublished = 'content.unpublished';
    case ContentDeleted = 'content.deleted';
    case RulesVersionBumped = 'rules.version_bumped';
    case TemplatesUpdated = 'templates.updated';
    case SettingsUpdated = 'settings.updated';
    case ServersMaintenanceToggled = 'servers.maintenance_toggled';

    public function severity(): string
    {
        return match ($this) {
            self::AdminLogin, self::AdminLogout, self::BranchesCreated, self::BranchesUpdated, self::BranchesMemberAdded,
            self::BranchesMemberRemoved, self::BranchesLeadChanged, self::ContentPublished, self::ContentUnpublished,
            self::ContentDeleted, self::RulesVersionBumped => 'info',
            self::RolesCreated, self::RolesUpdated, self::RolesDeleted, self::RolesPermissionsSynced,
            self::SuperAdminAssigned, self::SuperAdminRevoked, self::SuperAdminBreakGlass, self::MfaReset => 'critical',
            default => 'warning',
        };
    }

    public function requiresReason(): bool
    {
        return in_array($this, [
            self::UsersSuspended, self::UsersUnsuspended, self::UsersAnonymized, self::RolesAssigned, self::RolesRevoked,
            self::RolesCreated, self::RolesUpdated, self::RolesDeleted, self::RolesPermissionsSynced,
            self::SuperAdminAssigned, self::SuperAdminRevoked, self::SuperAdminBreakGlass,
            self::StaffOffboarded, self::MfaReset, self::RulesVersionBumped,
        ], true);
    }
}
