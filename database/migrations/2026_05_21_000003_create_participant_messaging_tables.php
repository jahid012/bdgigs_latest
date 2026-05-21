<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('suspended_at')->index();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('public_id')->constrained('users')->nullOnDelete();
            $table->string('context_type')->nullable()->after('gig_id')->index();
            $table->string('context_id')->nullable()->after('context_type')->index();
            $table->timestamp('last_message_at')->nullable()->after('seller_unread_count')->index();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('recipient_id')->nullable()->after('sender_id')->constrained('users')->nullOnDelete();
            $table->string('client_id')->nullable()->after('body')->index();
            $table->timestamp('read_at')->nullable()->after('sent_at')->index();
            $table->timestamp('email_reminder_sent_at')->nullable()->after('read_at')->index();
            $table->json('metadata')->nullable()->after('email_reminder_sent_at');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('context_role')->nullable()->index();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_read_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_typing_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamp('muted_at')->nullable()->index();
            $table->timestamp('last_email_reminded_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('url')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('token');
            $table->string('platform')->default('web')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->backfillParticipants();
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('conversation_participants');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_id');
            $table->dropColumn([
                'client_id',
                'read_at',
                'email_reminder_sent_at',
                'metadata',
            ]);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropColumn([
                'context_type',
                'context_id',
                'last_message_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }

    private function backfillParticipants(): void
    {
        if (! Schema::hasTable('conversations')) {
            return;
        }

        $now = now();

        DB::table('conversations')
            ->orderBy('id')
            ->each(function ($conversation) use ($now) {
                $updates = [
                    'context_type' => $conversation->gig_id ? 'gig' : 'profile',
                    'context_id' => $conversation->gig_id ? (string) $conversation->gig_id : null,
                    'created_by_id' => $conversation->buyer_id ?: $conversation->seller_id,
                    'last_message_at' => DB::table('messages')
                        ->where('conversation_id', $conversation->id)
                        ->max('sent_at') ?: $conversation->updated_at,
                ];

                DB::table('conversations')
                    ->where('id', $conversation->id)
                    ->update($updates);

                foreach ([
                    ['user_id' => $conversation->buyer_id, 'role' => 'buying', 'unread' => $conversation->buyer_unread_count],
                    ['user_id' => $conversation->seller_id, 'role' => 'selling', 'unread' => $conversation->seller_unread_count],
                ] as $participant) {
                    if (! $participant['user_id']) {
                        continue;
                    }

                    DB::table('conversation_participants')->updateOrInsert(
                        [
                            'conversation_id' => $conversation->id,
                            'user_id' => $participant['user_id'],
                        ],
                        [
                            'context_role' => $participant['role'],
                            'unread_count' => (int) $participant['unread'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            });
    }
};
