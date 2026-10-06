<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ic_number') || ! Schema::hasColumn('users', 'password')) {
            return;
        }

        $hasPasswordChangedAt = Schema::hasColumn('users', 'password_changed_at');
        DB::transaction(function () use ($hasPasswordChangedAt): void {
            DB::table('users')->select('id', 'ic_number')->orderBy('id')->chunkById(500, function ($users) use ($hasPasswordChangedAt): void {
                foreach ($users as $user) {
                    $icNumber = trim((string) $user->ic_number);
                    if ($icNumber === '') {
                        continue;
                    }

                    $updates = ['password' => Hash::make($icNumber)];
                    if ($hasPasswordChangedAt) {
                        $updates['password_changed_at'] = null;
                    }
                    DB::table('users')->where('id', $user->id)->update($updates);
                }
            });
        });
    }

    public function down(): void
    {
        // Password hashes cannot be restored; rolling back keeps the new credentials valid.
    }
};
