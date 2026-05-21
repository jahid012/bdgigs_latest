<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('seller_name');
            $table->string('seller_avatar')->nullable();
            $table->string('seller_level')->nullable();
            $table->string('badge')->nullable();
            $table->string('image')->nullable();
            $table->string('category_id')->nullable()->index();
            $table->string('category_label')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('reviews')->default(0);
            $table->unsignedSmallInteger('delivery_days')->default(3);
            $table->json('seller_details')->nullable();
            $table->json('service_options')->nullable();
            $table->boolean('pro')->default(false);
            $table->boolean('instant')->default(false);
            $table->boolean('consultation')->default(false);
            $table->boolean('featured')->default(false);
            $table->string('search_text')->nullable();
            $table->string('tag')->nullable();
            $table->string('orders_label')->default('0 active');
            $table->string('conversion_label')->default('New listing');
            $table->string('status')->default('Live');
            $table->string('status_class')->default('status-completed');
            $table->json('packages')->nullable();
            $table->json('extras')->nullable();
            $table->json('requirements')->nullable();
            $table->json('gallery_images')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gig_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'gig_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gig_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service');
            $table->string('buyer_name')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('status');
            $table->string('status_class');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedInteger('earnings_cents')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gig_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->string('buyer_name');
            $table->string('seller_name');
            $table->string('status');
            $table->string('status_class');
            $table->string('priority')->nullable();
            $table->unsignedInteger('buyer_unread_count')->default(0);
            $table->unsignedInteger('seller_unread_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('detail');
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('saved_services');
        Schema::dropIfExists('gigs');
    }
};
