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
        Schema::create('offering_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offering_id');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key with cascade delete
            $table->foreign('offering_id')
                ->references('id')
                ->on('offerings')
                ->onDelete('cascade');

            // Indexes
            $table->index(['offering_id', 'date']);
            $table->index('date');

            // Unique constraint: one record per offering per day
            $table->unique(['offering_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_days');
    }
};
