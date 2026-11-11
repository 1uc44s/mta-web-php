<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * docs/ADMIN.md §5.3, §6 (adım 6): süresi dolan rol ve branş üyeliklerini kaldırır, audit yazar. Saatlik zamanlanır.
 * Son personel rolü düşen kullanıcının branşları silinir ve oturumları kapatılır.
 */
#[Signature('mrp:expire-roles')]
#[Description('Süresi dolmuş rol ve branş üyeliklerini kaldırır')]
final class ExpireRolesCommand extends Command
{
    public function handle(AuditLogger $audit, PermissionResolver $resolver): int
    {
        $now = now();
        $expired = DB::table('user_roles')->whereNotNull('expires_at')->where('expires_at', '<=', $now)->get(['id', 'user_id', 'role_id']);

        foreach ($expired as $row) {
            $user = User::query()->find($row->user_id);
            $role = Role::query()->find($row->role_id);
            DB::table('user_roles')->where('id', $row->id)->delete();

            if ($user !== null && $role !== null) {
                $stillStaff = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->where('user_roles.user_id', $user->id)->where('roles.is_staff', true)->exists();

                if (! $stillStaff) {
                    DB::table('branch_members')->where('user_id', $user->id)->delete();
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                $audit->record(AuditAction::RolesExpired, $user, ['before' => ['role' => $role->slug]], null, null, 'scheduler');
            }

            $resolver->flush((int) $row->user_id);
        }

        $members = DB::table('branch_members')->whereNotNull('expires_at')->where('expires_at', '<=', $now)->get(['id', 'user_id']);
        DB::table('branch_members')->whereIn('id', $members->pluck('id'))->delete();
        $members->pluck('user_id')->unique()->each(fn ($id) => $resolver->flush((int) $id));

        $this->info("Kaldırılan rol: {$expired->count()}, branş üyeliği: {$members->count()}");

        return self::SUCCESS;
    }
}
