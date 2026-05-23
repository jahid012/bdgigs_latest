<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->string('account_holder');
            $table->string('account_number');
            $table->string('routing_details')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_payout_method_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('approved_amount_cents')->nullable();
            $table->string('currency', 8)->default('USD');
            $table->json('payout_snapshot');
            $table->string('status')->default('pending')->index();
            $table->text('seller_note')->nullable();
            $table->text('review_note')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_activities');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('seller_payout_methods');
    }
};
