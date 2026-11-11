<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('project_documents', 'review_feedback')) {
            Schema::table('project_documents', fn (Blueprint $table) => $table->text('review_feedback')->nullable());
        }
    }

    public function down(): void
    {
        // Preserve review feedback and submission history on rollback.
    }
};
