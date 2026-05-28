<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('work_started_at')->nullable()->after('due_date')->index();
            $table->timestamp('overdue_at')->nullable()->after('work_started_at')->index();
            $table->timestamp('cancelled_at')->nullable()->after('overdue_at')->index();
            $table->string('cancellation_status')->nullable()->after('cancelled_at')->index();
            $table->string('refund_status')->nullable()->after('cancellation_status')->index();
            $table->timestamp('review_period_expires_at')->nullable()->after('refund_status')->index();
            $table->timestamp('review_period_expired_at')->nullable()->after('review_period_expires_at')->index();
            $table->timestamp('reviews_visible_at')->nullable()->after('review_period_expired_at')->index();
        });

        Schema::create('order_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('key');
            $table->timestamp('sent_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'key']);
        });

        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->text('reason')->nullable();
            $table->text('response_note')->nullable();
            $table->string('refund_status')->nullable()->index();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('responded_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('custom_offers', function (Blueprint $table) {
            $table->timestamp('payment_failed_at')->nullable()->after('paid_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('custom_offers', function (Blueprint $table) {
            $table->dropIndex(['payment_failed_at']);
            $table->dropColumn('payment_failed_at');
        });

        Schema::dropIfExists('order_cancellations');
        Schema::dropIfExists('order_reminders');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['work_started_at']);
            $table->dropIndex(['overdue_at']);
            $table->dropIndex(['cancelled_at']);
            $table->dropIndex(['cancellation_status']);
            $table->dropIndex(['refund_status']);
            $table->dropIndex(['review_period_expires_at']);
            $table->dropIndex(['review_period_expired_at']);
            $table->dropIndex(['reviews_visible_at']);

            $table->dropColumn([
                'work_started_at',
                'overdue_at',
                'cancelled_at',
                'cancellation_status',
                'refund_status',
                'review_period_expires_at',
                'review_period_expired_at',
                'reviews_visible_at',
            ]);
        });
    }
};
