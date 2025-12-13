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
        Schema::create('offering_time_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offering_day_id');
            $table->unsignedBigInteger('offering_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('booked_count')->default(0);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys with cascade delete
            $table->foreign('offering_day_id')
                ->references('id')
                ->on('offering_days')
                ->onDelete('cascade');

            $table->foreign('offering_id')
                ->references('id')
                ->on('offerings')
                ->onDelete('cascade');

            // Indexes
            $table->index('offering_day_id');
            $table->index('offering_id');
            $table->index(['offering_id', 'start_time']);

            // Unique constraint: no duplicate slots on same day
            $table->unique(['offering_day_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_time_slots');
    }
};
