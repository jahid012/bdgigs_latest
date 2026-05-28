<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('seller_status')->default('not_applied')->after('profile_type')->index();
            $table->text('seller_status_reason')->nullable()->after('seller_status');
            $table->foreignId('seller_status_reviewed_by')->nullable()->after('seller_status_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('seller_status_reviewed_at')->nullable()->after('seller_status_reviewed_by');
            $table->timestamp('profile_completion_reminded_at')->nullable()->after('last_seen_at')->index();
            $table->timestamp('marketing_unsubscribed_at')->nullable()->after('profile_completion_reminded_at')->index();
        });

        Schema::create('seller_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('gigs', function (Blueprint $table) {
            $table->timestamp('submitted_for_review_at')->nullable()->after('status_class')->index();
            $table->foreignId('moderated_by')->nullable()->after('submitted_for_review_at')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->text('moderation_reason')->nullable()->after('moderated_at');
            $table->timestamp('paused_at')->nullable()->after('moderation_reason');
            $table->timestamp('deactivated_at')->nullable()->after('paused_at')->index();
        });

        Schema::create('gig_moderation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('identity_verification_submissions', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('reviewed_by');
            $table->timestamp('additional_document_requested_at')->nullable()->after('review_note');
            $table->text('additional_document_note')->nullable()->after('additional_document_requested_at');
            $table->json('metadata')->nullable()->after('additional_document_note');
        });

        Schema::create('moderation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('reportable');
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->string('reason');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('suspicious_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('severity')->default('medium')->index();
            $table->string('ip_address')->nullable()->index();
            $table->string('user_agent')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_preference_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->string('email_type')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('email_template_key');
            $table->string('category')->default('marketing')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('email_campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign_key')->index();
            $table->string('email_template_key');
            $table->string('status')->default('queued')->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'campaign_key', 'email_template_key'], 'campaign_user_template_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaign_logs');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('email_preference_tokens');
        Schema::dropIfExists('suspicious_activity_logs');
        Schema::dropIfExists('moderation_reports');

        Schema::table('identity_verification_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'review_note',
                'additional_document_requested_at',
                'additional_document_note',
                'metadata',
            ]);
        });

        Schema::dropIfExists('gig_moderation_events');

        Schema::table('gigs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn([
                'submitted_for_review_at',
                'moderated_at',
                'moderation_reason',
                'paused_at',
                'deactivated_at',
            ]);
        });

        Schema::dropIfExists('seller_status_events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_status_reviewed_by');
            $table->dropColumn([
                'seller_status',
                'seller_status_reason',
                'seller_status_reviewed_at',
                'profile_completion_reminded_at',
                'marketing_unsubscribed_at',
            ]);
        });
    }
};
