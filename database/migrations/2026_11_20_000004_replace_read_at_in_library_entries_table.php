<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('library_entries', function (Blueprint $table): void {
            $table->unsignedSmallInteger('read_count')->default(0)->after('is_favorite');
            $table->date('last_read_at')->nullable()->after('read_count');

            $table->index(['user_id', 'read_count']);
        });

        $readEntries = DB::table('library_entries')
            ->whereNotNull('read_at')
            ->orWhere('status', 'read')
            ->get(['id', 'user_id', 'book_id', 'read_at', 'created_at', 'updated_at']);

        foreach ($readEntries as $entry) {
            $finishedAt = $entry->read_at !== null
                ? substr((string) $entry->read_at, 0, 10)
                : ($entry->updated_at !== null ? substr((string) $entry->updated_at, 0, 10) : now()->toDateString());

            $logExists = DB::table('reading_logs')
                ->where('user_id', $entry->user_id)
                ->where('book_id', $entry->book_id)
                ->exists();

            if (! $logExists) {
                DB::table('reading_logs')->insert([
                    'user_id' => $entry->user_id,
                    'book_id' => $entry->book_id,
                    'started_at' => null,
                    'finished_at' => $finishedAt,
                    'is_reread' => false,
                    'created_at' => $entry->created_at ?? now(),
                    'updated_at' => $entry->updated_at ?? now(),
                ]);
            }

            DB::table('library_entries')
                ->where('id', $entry->id)
                ->update([
                    'read_count' => 1,
                    'last_read_at' => $finishedAt,
                ]);
        }

        Schema::table('library_entries', function (Blueprint $table): void {
            $table->dropColumn('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_entries', function (Blueprint $table): void {
            $table->date('read_at')->nullable()->after('is_favorite');
        });

        DB::table('library_entries')
            ->whereNotNull('last_read_at')
            ->update(['read_at' => DB::raw('last_read_at')]);

        Schema::table('library_entries', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'read_count']);
            $table->dropColumn(['read_count', 'last_read_at']);
        });
    }
};
