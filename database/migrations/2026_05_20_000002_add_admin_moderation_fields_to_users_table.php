<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_type')->default('buyer')->after('password')->index();
            $table->string('country')->nullable()->after('profile_type');
            $table->string('verification_status')->default('active')->after('country')->index();
            $table->timestamp('suspended_at')->nullable()->after('verification_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_type',
                'country',
                'verification_status',
                'suspended_at',
            ]);
        });
    }
};
