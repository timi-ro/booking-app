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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offering_time_slot_id');
            $table->unsignedBigInteger('offering_id');
            $table->unsignedBigInteger('user_id');
            $table->string('booking_reference', 50)->unique();
            $table->enum('status', ['confirmed', 'cancelled', 'completed', 'no_show'])->default('confirmed');
            $table->decimal('total_price', 10, 2);
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->string('payment_id')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys (no cascade - keep booking history)
            $table->foreign('offering_time_slot_id')
                ->references('id')
                ->on('offering_time_slots');

            $table->foreign('offering_id')
                ->references('id')
                ->on('offerings');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            // Indexes
            $table->index('offering_time_slot_id');
            $table->index('offering_id');
            $table->index('user_id');
            $table->index(['user_id', 'status']);
            $table->index('booking_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
