<?php

namespace App\Offering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferingTimeSlot extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'offering_day_id',
        'offering_id',
        'start_time',
        'end_time',
        'capacity',
        'booked_count',
        'price_override',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'price_override' => 'decimal:2',
    ];

    /**
     * Get the offering day that this time slot belongs to.
     */
    public function offeringDay()
    {
        return $this->belongsTo(OfferingDay::class);
    }

    /**
     * Get the offering that this time slot belongs to.
     */
    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }
}
