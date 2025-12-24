<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'offering_time_slot_id',
        'offering_id',
        'user_id',
        'booking_reference',
        'status',
        'total_price',
        'payment_status',
        'payment_id',
        'customer_notes',
        'cancellation_reason',
        'cancelled_at',
        'confirmed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_price' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the offering for this booking.
     */
    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    /**
     * Get the user (customer) for this booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the time slot for this booking.
     */
    public function timeSlot()
    {
        return $this->belongsTo(OfferingTimeSlot::class, 'offering_time_slot_id');
    }
}
