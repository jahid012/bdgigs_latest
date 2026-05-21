<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->text('avatar')->nullable()->after('country');
            $table->timestamp('deactivated_at')->nullable()->after('suspended_at')->index();
        });

        DB::table('users')
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->each(function ($user): void {
                $base = Str::slug($user->name ?: Str::before($user->email, '@'), '_') ?: 'user';
                $candidate = $base;
                $suffix = 1;

                while (DB::table('users')
                    ->where('username', $candidate)
                    ->where('id', '!=', $user->id)
                    ->exists()) {
                    $suffix++;
                    $candidate = "{$base}_{$suffix}";
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            });

        Schema::create('buyer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('overview')->nullable();
            $table->json('working_days')->nullable();
            $table->json('working_hours')->nullable();
            $table->string('timezone')->nullable();
            $table->json('languages')->nullable();
            $table->timestamps();
        });

        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('professional_title')->nullable();
            $table->text('about')->nullable();
            $table->json('languages')->nullable();
            $table->json('skills')->nullable();
            $table->json('portfolio_projects')->nullable();
            $table->json('work_experience')->nullable();
            $table->json('education')->nullable();
            $table->json('certification')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('company')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('tax_id')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('preferences')->nullable();
            $table->boolean('realtime_enabled')->default(true);
            $table->boolean('sound_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('identity_verification_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('review')->index();
            $table->json('details')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_saves');
        Schema::dropIfExists('identity_verification_submissions');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('billing_profiles');
        Schema::dropIfExists('seller_profiles');
        Schema::dropIfExists('buyer_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar', 'deactivated_at']);
        });
    }
};
