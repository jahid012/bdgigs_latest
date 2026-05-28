<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->json('available_variables')->nullable();
            $table->string('category')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email_template_key')->nullable()->index();
            $table->string('recipient_email')->index();
            $table->string('subject');
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('user_email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email_type');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'email_type']);
            $table->index(['email_type', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_email_preferences');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
    }
};
