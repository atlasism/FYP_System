<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('full_name', 100);
            $table->string('email', 100)->unique();
            $table->string('ic_number', 30);
            $table->string('matric_no', 30)->nullable();
            $table->enum('role', ['Admin', 'Student', 'Supervisor', 'Panel']);
            $table->string('department', 30)->default('JTMK');
            $table->string('program_name', 120)->default('JTMK - Information Technology');
            $table->string('course_code', 20)->default('DFT50114');
            $table->string('track', 100)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->string('phone_no', 30)->nullable();
            $table->string('profile_picture')->nullable();
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables may predate Laravel when a legacy SQL dump is imported.
        // Keep them on rollback so application data cannot be dropped by mistake.
    }
};
