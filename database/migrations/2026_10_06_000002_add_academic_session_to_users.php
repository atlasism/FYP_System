<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'academic_session')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('academic_session', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Retain roster session data on rollback to protect imported accounts.
    }
};
