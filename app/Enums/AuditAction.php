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
    case SupportEscalated = 'support.escalated';
    case ContentPublished = 'content.published';
    case ContentUnpublished = 'content.unpublished';
    case ContentDeleted = 'content.deleted';
    case RulesVersionBumped = 'rules.version_bumped';
    case TemplatesUpdated = 'templates.updated';
    case SettingsUpdated = 'settings.updated';
    case ServersMaintenanceToggled = 'servers.maintenance_toggled';
    case BridgeSignatureAlert = 'bridge.signature_alert';
    case AccountsLinked = 'accounts.linked';
    case AccountsUnlinkRequested = 'accounts.unlink_requested';
    case AccountsUnlinked = 'accounts.unlinked';
    case AccountsUnlinkRejected = 'accounts.unlink_rejected';
    case AccountsLinkRevoked = 'accounts.link_revoked';
    case AccountsManualLinked = 'accounts.manual_linked';
    case CharactersVisibilityChanged = 'characters.visibility_changed';
    case IntegrationsCredentialCreated = 'integrations.credential_created';
    case IntegrationsCredentialRevoked = 'integrations.credential_revoked';
    case IntegrationsServerUpdated = 'integrations.server_updated';
    case DevicesApproved = 'devices.approved';
    case DevicesRejected = 'devices.rejected';
    case DevicesRevoked = 'devices.revoked';
    case ApplicationsDecided = 'applications.decided';
    case SanctionsCreated = 'sanctions.created';
    case SanctionsRevoked = 'sanctions.revoked';
    case SanctionsAppealDecided = 'sanctions.appeal_decided';
    case GameCommandsDispatched = 'game_commands.dispatched';
    case GameCommandsFailed = 'game_commands.failed';

    public function severity(): string
    {
        return match ($this) {
            self::AdminLogin, self::AdminLogout, self::BranchesCreated, self::BranchesUpdated, self::BranchesMemberAdded,
            self::BranchesMemberRemoved, self::BranchesLeadChanged, self::SupportEscalated, self::ContentPublished, self::ContentUnpublished,
            self::ContentDeleted, self::RulesVersionBumped => 'info',
            self::RolesCreated, self::RolesUpdated, self::RolesDeleted, self::RolesPermissionsSynced,
            self::SuperAdminAssigned, self::SuperAdminRevoked, self::SuperAdminBreakGlass, self::MfaReset,
            self::BridgeSignatureAlert, self::IntegrationsCredentialCreated, self::IntegrationsCredentialRevoked => 'critical',
            self::AccountsLinked, self::AccountsUnlinkRequested, self::CharactersVisibilityChanged, self::ApplicationsDecided => 'info',
            default => 'warning',
        };
    }

    public function requiresReason(): bool
    {
        return in_array($this, [
            self::UsersSuspended, self::UsersUnsuspended, self::UsersAnonymized, self::RolesAssigned, self::RolesRevoked,
            self::RolesCreated, self::RolesUpdated, self::RolesDeleted, self::RolesPermissionsSynced,
            self::SuperAdminAssigned, self::SuperAdminRevoked, self::SuperAdminBreakGlass,
            self::StaffOffboarded, self::MfaReset, self::RulesVersionBumped, self::SupportEscalated,
            self::AccountsUnlinked, self::AccountsUnlinkRejected, self::AccountsLinkRevoked, self::AccountsManualLinked,
            self::CharactersVisibilityChanged, self::IntegrationsCredentialRevoked,
            self::DevicesApproved, self::DevicesRejected, self::DevicesRevoked, self::ApplicationsDecided,
            self::SanctionsCreated, self::SanctionsRevoked, self::SanctionsAppealDecided,
        ], true);
    }
}
