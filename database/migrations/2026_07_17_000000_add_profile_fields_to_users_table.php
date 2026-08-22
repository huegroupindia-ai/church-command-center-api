<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('church_id')->nullable()->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('role')->default('volunteer')->after('avatar');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('fcm_token')->nullable()->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'church_id', 'phone', 'avatar', 'role',
                'is_active', 'fcm_token', 'last_login_at',
            ]);
        });
    }
};
