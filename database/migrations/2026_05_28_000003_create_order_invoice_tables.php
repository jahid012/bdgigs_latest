<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status_class')->index();
            $table->timestamp('paid_at')->nullable()->after('payment_status')->index();
            $table->string('payment_method')->nullable()->after('paid_at');
            $table->string('transaction_id')->nullable()->after('payment_method')->index();
            $table->timestamp('refunded_at')->nullable()->after('transaction_id')->index();
            $table->unsignedInteger('refund_amount_cents')->default(0)->after('refunded_at');
        });

        Schema::create('order_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('currency', 8)->default('USD');
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('platform_fee_cents')->default(0);
            $table->unsignedInteger('seller_earning_cents')->default(0);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('issued_at')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_invoices');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['refunded_at']);

            $table->dropColumn([
                'payment_status',
                'paid_at',
                'payment_method',
                'transaction_id',
                'refunded_at',
                'refund_amount_cents',
            ]);
        });
    }
};
