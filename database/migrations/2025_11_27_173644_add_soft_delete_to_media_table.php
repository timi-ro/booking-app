<?php

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
        Schema::table('media', function (Blueprint $table) {
            $table->string('path')->nullable()->change();

            // Step 1: Add uuid column WITHOUT unique first
            $table->string('uuid')->nullable();

            $table->string('original_filename')->nullable();
            $table->enum('status', ['processing', 'uploading', 'completed', 'failed', 'deleted'])->default('uploading');
            $table->softDeletes();
        });

        // Step 2: Populate uuid for existing rows
        // Use different UUID generation based on database driver
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::table('media')->whereNull('uuid')->update([
                'uuid' => DB::raw('(UUID())')
            ]);
        } else {
            // For SQLite and other databases, generate UUID in PHP
            $media = DB::table('media')->whereNull('uuid')->get();
            foreach ($media as $item) {
                DB::table('media')
                    ->where('id', $item->id)
                    ->update(['uuid' => Str::uuid()->toString()]);
            }
        }

        // Step 3: Now add UNIQUE constraint safely
        Schema::table('media', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
            $table->dropColumn('original_filename');
            $table->dropColumn('status');
            $table->dropSoftDeletes();
        });
    }
};
