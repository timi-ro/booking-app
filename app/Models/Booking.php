<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $casts = [
        'total_price' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(OfferingTimeSlot::class, 'offering_time_slot_id');
    }
}
