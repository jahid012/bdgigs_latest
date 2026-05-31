<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->string('title')->nullable();
            $table->string('department')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->addAdminAuditColumns();
        $this->copyExistingAdminUsers();
    }

    public function down(): void
    {
        $this->dropAdminAuditColumns();

        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::dropIfExists('admins');
    }

    private function addAdminAuditColumns(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('seller_status_reviewed_by_admin_id')->nullable()->after('seller_status_reviewed_by')->constrained('admins')->nullOnDelete();
            $table->foreignId('suspended_by_admin_id')->nullable()->after('suspended_by')->constrained('admins')->nullOnDelete();
            $table->foreignId('deactivated_by_admin_id')->nullable()->after('deactivated_by')->constrained('admins')->nullOnDelete();
        });

        Schema::table('account_status_events', function (Blueprint $table) {
            $table->foreignId('actor_admin_id')->nullable()->after('actor_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('seller_status_events', function (Blueprint $table) {
            $table->foreignId('actor_admin_id')->nullable()->after('actor_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('gigs', function (Blueprint $table) {
            $table->foreignId('moderated_by_admin_id')->nullable()->after('moderated_by')->constrained('admins')->nullOnDelete();
        });

        Schema::table('gig_moderation_events', function (Blueprint $table) {
            $table->foreignId('actor_admin_id')->nullable()->after('actor_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('identity_verification_submissions', function (Blueprint $table) {
            $table->foreignId('reviewed_by_admin_id')->nullable()->after('reviewed_by')->constrained('admins')->nullOnDelete();
        });

        Schema::table('manual_payment_submissions', function (Blueprint $table) {
            $table->foreignId('reviewed_by_admin_id')->nullable()->after('reviewed_by')->constrained('admins')->nullOnDelete();
        });

        Schema::table('order_activities', function (Blueprint $table) {
            $table->foreignId('actor_admin_id')->nullable()->after('actor_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->foreignId('reviewed_by_admin_id')->nullable()->after('reviewed_by')->constrained('admins')->nullOnDelete();
            $table->foreignId('paid_by_admin_id')->nullable()->after('paid_by')->constrained('admins')->nullOnDelete();
        });

        Schema::table('withdrawal_activities', function (Blueprint $table) {
            $table->foreignId('actor_admin_id')->nullable()->after('actor_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->foreignId('opened_by_admin_id')->nullable()->after('opened_by_id')->constrained('admins')->nullOnDelete();
            $table->foreignId('assigned_to_admin_id')->nullable()->after('assigned_to_id')->constrained('admins')->nullOnDelete();
            $table->foreignId('resolved_by_admin_id')->nullable()->after('resolved_by_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('dispute_activities', function (Blueprint $table) {
            $table->foreignId('actor_admin_id')->nullable()->after('actor_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('moderation_reports', function (Blueprint $table) {
            $table->foreignId('assigned_to_admin_id')->nullable()->after('assigned_to_id')->constrained('admins')->nullOnDelete();
            $table->foreignId('resolved_by_admin_id')->nullable()->after('resolved_by_id')->constrained('admins')->nullOnDelete();
        });

        Schema::table('suspicious_activity_logs', function (Blueprint $table) {
            $table->foreignId('reviewed_by_admin_id')->nullable()->after('reviewed_by')->constrained('admins')->nullOnDelete();
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->foreignId('updated_by_admin_id')->nullable()->after('meta')->constrained('admins')->nullOnDelete();
        });
    }

    private function dropAdminAuditColumns(): void
    {
        foreach ([
            'platform_settings' => ['updated_by_admin_id'],
            'suspicious_activity_logs' => ['reviewed_by_admin_id'],
            'moderation_reports' => ['assigned_to_admin_id', 'resolved_by_admin_id'],
            'dispute_activities' => ['actor_admin_id'],
            'disputes' => ['opened_by_admin_id', 'assigned_to_admin_id', 'resolved_by_admin_id'],
            'withdrawal_activities' => ['actor_admin_id'],
            'withdrawal_requests' => ['reviewed_by_admin_id', 'paid_by_admin_id'],
            'order_activities' => ['actor_admin_id'],
            'manual_payment_submissions' => ['reviewed_by_admin_id'],
            'identity_verification_submissions' => ['reviewed_by_admin_id'],
            'gig_moderation_events' => ['actor_admin_id'],
            'gigs' => ['moderated_by_admin_id'],
            'seller_status_events' => ['actor_admin_id'],
            'account_status_events' => ['actor_admin_id'],
            'users' => ['seller_status_reviewed_by_admin_id', 'suspended_by_admin_id', 'deactivated_by_admin_id'],
        ] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existingColumns = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn($table, $column)
            ));

            if ($existingColumns === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($existingColumns) {
                foreach ($existingColumns as $column) {
                    $blueprint->dropConstrainedForeignId($column);
                }
            });
        }
    }

    private function copyExistingAdminUsers(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $adminRoleIds = DB::table('roles')
            ->whereIn('name', ['super_admin', 'admin', 'finance_manager', 'support_agent', 'catalog_moderator', 'trust_safety'])
            ->pluck('id');

        if ($adminRoleIds->isEmpty()) {
            return;
        }

        $adminUserIds = DB::table('model_has_roles')
            ->whereIn('role_id', $adminRoleIds)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id')
            ->unique();

        $now = now();

        foreach ($adminUserIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();

            if (! $user) {
                continue;
            }

            $adminId = DB::table('admins')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'status' => $user->suspended_at ? 'suspended' : 'active',
                'last_seen_at' => $user->last_seen_at ?? null,
                'created_at' => $user->created_at ?? $now,
                'updated_at' => $user->updated_at ?? $now,
            ]);

            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->whereIn('role_id', $adminRoleIds)
                ->update([
                    'model_type' => 'App\\Models\\Admin',
                    'model_id' => $adminId,
                ]);
        }

        DB::table('roles')->where('guard_name', 'web')->update(['guard_name' => 'admin']);
        DB::table('permissions')->where('guard_name', 'web')->update(['guard_name' => 'admin']);
    }
};
