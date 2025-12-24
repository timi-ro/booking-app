<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferingDay extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'offering_id',
        'date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the offering that this day belongs to.
     */
    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    /**
     * Get the time slots for this offering day.
     */
    public function timeSlots()
    {
        return $this->hasMany(OfferingTimeSlot::class);
    }
}
