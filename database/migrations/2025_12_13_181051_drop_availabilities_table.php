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
        Schema::dropIfExists('availabilities');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offering_id');
            $table->json('details');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('offering_id')
                ->references('id')
                ->on('offerings')
                ->onDelete('cascade');
        });
    }
};
