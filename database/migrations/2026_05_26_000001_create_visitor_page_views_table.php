<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_id', 80)->nullable()->index();
            $table->string('session_id', 128)->nullable()->index();
            $table->string('path', 512);
            $table->string('page_title')->nullable();
            $table->string('referrer', 512)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['visited_at', 'is_bot']);
            $table->index(['user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_page_views');
    }
};
